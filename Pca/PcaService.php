<?php

namespace Providers\Pca;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Providers\Pca\PcaRepository;
use Providers\Pca\PcaCredentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Pca\Contracts\ICredentials;
use Providers\Pca\Exceptions\WalletErrorException;
use Providers\Pca\Exceptions\InvalidTokenException;
use Providers\Pca\Exceptions\InsufficientFundException;
use Providers\Pca\Exceptions\RefundTransactionNotFoundException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Pca\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use Providers\Pca\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class PcaService
{
    private const PROVIDER_TIMEZONE = 'GMT+0';

    public function __construct(
        private PcaRepository $repository,
        private PcaCredentials $credentials,
        private PcaApi $api,
        private Randomizer $randomizer,
        private IWallet $wallet,
        private WalletReport $report,
    ) {
    }

    public function getLaunchUrl(Request $request): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $request->playId);

        if (is_null($player) === true)
            $this->repository->createPlayer(
                playID: $request->playId,
                currency: $request->currency,
                username: $request->username
            );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $token = "{$credentials->getKioskName()}_{$this->randomizer->createToken()}";

        $this->repository->createOrUpdateToken(playID: $request->playId, token: $token);

        return $this->api->getGameLaunchUrl(credentials: $credentials, request: $request, token: $token);
    }

    public function getBetDetail(Request $request): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($player) === true)
            throw new CasinoPlayerNotFoundException;

        $transaction = $this->repository->getTransactionByRefID(refID: $request->bet_id);

        if (is_null($transaction) === true)
            throw new CasinoTransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        return $this->api->gameRoundStatus(credentials: $credentials, transactionID: $transaction->trx_id);
    }

    private function validateToken(Request $request, object $player): void
    {
        $playGame = $this->repository->getPlayGameByPlayIDToken(
            playID: $player->play_id,
            token: $request->externalToken
        );

        if (is_null($playGame) === true)
            throw new InvalidTokenException(request: $request);
    }

    private function getPlayerDetails(Request $request): object
    {
        $playID = explode('_', $request->username)[1] ?? null;

        $player = $playID == null ? null : $this->repository->getPlayerByPlayID(playID: strtolower($playID));

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException(request: $request);

        return $player;
    }

    private function getPlayerBalance(ICredentials $credentials, Request $request, string $playID): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($walletResponse['status_code'] !== 2100)
            throw new WalletErrorException($request);

        return $walletResponse['credit'];
    }

    public function authenticate(Request $request): string
    {
        $player = $this->getPlayerDetails(request: $request);

        $this->validateToken(request: $request, player: $player);

        return $player->currency;
    }

    public function getBalance(Request $request): float
    {
        $player = $this->getPlayerDetails(request: $request);

        $this->validateToken(request: $request, player: $player);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        return $this->getPlayerBalance(credentials: $credentials, request: $request, playID: $player->play_id);
    }

    public function logout(Request $request): void
    {
        $player = $this->getPlayerDetails(request: $request);

        $this->validateToken(request: $request, player: $player);

        $this->repository->deleteToken($player->play_id, $request->externalToken);
    }

    public function bet(Request $request): float
    {
        $player = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);
        $balance = $this->getPlayerBalance(credentials: $credentials, request: $request, playID: $player->play_id);

        if ($balance < (float) $request->amount)
            throw new InsufficientFundException(request: $request);

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $betTime = Carbon::parse($request->transactionDate, self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $transaction = $this->repository->getTransactionByTransactionIDRefID(
                transactionID: $request->gameRoundCode,
                refID: $request->transactionCode
            );

            if (is_null($transaction) === true) {
                $this->validateToken(request: $request, player: $player);

                $this->repository->createBetTransaction(
                    player: $player,
                    request: $request,
                    betTime: $betTime
                );
            }

            $report = $this->report->makeCasinoReport(
                trxID: $request->transactionCode,
                gameCode: $request->gameCodeName,
                betTime: $betTime,
                betChoice: '-',
                result: '-'
            );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $player->play_id,
                currency: $player->currency,
                wagerTransactionID: "wagerPayout-{$request->transactionCode}",
                wagerAmount: (float) $request->amount,
                payoutTransactionID: "wagerPayout-{$request->transactionCode}",
                payoutAmount: 0,
                report: $report
            );

            if (in_array($walletResponse['status_code'], [2100, 2102]) === false)
                throw new WalletErrorException($request);

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function settle(Request $request): float
    {
        $player = $this->getPlayerDetails($request);

        $betTransaction = $this->repository->getBetTransactionByTransactionID(transactionID: $request->gameRoundCode);

        if (is_null($betTransaction) === true)
            throw new ProviderTransactionNotFoundException(request: $request);

        $refID = is_null($request->pay) === true ? "L-{$request->requestId}" : $request->pay['transactionCode'];

        $settleTransaction = $this->repository->getTransactionByTransactionIDRefID(
            transactionID: $request->gameRoundCode,
            refID: $refID
        );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if (is_null($request->pay) === true) {
            if (is_null($settleTransaction) === true) {
                $this->repository->createLoseTransaction(
                    player: $player,
                    request: $request
                );
            }

            return $this->getPlayerBalance(credentials: $credentials, request: $request, playID: $player->play_id);
        }

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $settleTime = Carbon::parse($request->pay['transactionDate'], self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            if (is_null($settleTransaction) === true) {
                $this->repository->createSettleTransaction(
                    player: $player,
                    request: $request,
                    settleTime: $settleTime
                );
            }

            $report = $this->report->makeCasinoReport(
                trxID: $request->pay['transactionCode'],
                gameCode: $request->gameCodeName,
                betTime: $settleTime,
                betChoice: '-',
                result: '-'
            );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $player->play_id,
                currency: $player->currency,
                wagerTransactionID: "wagerPayout-{$refID}",
                wagerAmount: 0,
                payoutTransactionID: "wagerPayout-{$refID}",
                payoutAmount: (float) $request->pay['amount'],
                report: $report
            );

            if (in_array($walletResponse['status_code'], [2100, 2102]) === false)
                throw new WalletErrorException($request);

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function refund(Request $request): float
    {
        $player = $this->getPlayerDetails($request);

        $betTransaction = $this->repository->getBetTransactionByTransactionIDRefID(
            transactionID: $request->gameRoundCode,
            refID: $request->pay['relatedTransactionCode']
        );

        if (is_null($betTransaction) === true)
            throw new RefundTransactionNotFoundException(request: $request);

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $refundTime = Carbon::parse($request->pay['transactionDate'], self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $refundTransaction = $this->repository->getRefundTransactionByTransactionIDRefID(
                transactionID: $request->gameRoundCode,
                refID: $request->pay['relatedTransactionCode']
            );

            if (is_null($refundTransaction) === true) {
                $this->repository->createRefundTransaction(
                    player: $player,
                    request: $request,
                    refundTime: $refundTime
                );
            }

            $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

            $walletResponse = $this->wallet->resettle(
                credentials: $credentials,
                playID: $player->play_id,
                currency: $player->currency,
                transactionID: "resettle-{$request->pay['relatedTransactionCode']}",
                amount: (float) $request->pay['amount'],
                betID: $request->pay['relatedTransactionCode'],
                settledTransactionID: "wager-{$request->pay['relatedTransactionCode']}",
                betTime: $refundTime
            );

            if (in_array($walletResponse['status_code'], [2100, 2102]) === false)
                throw new WalletErrorException($request);

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}