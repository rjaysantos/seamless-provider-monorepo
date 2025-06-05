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
    ) {}

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

        $transaction = $this->repository->getTransactionByBetID(betID: $request->bet_id);

        if (is_null($transaction) === true)
            throw new CasinoTransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        return $this->api->gameRoundStatus(credentials: $credentials, transactionID: $transaction->ref_id);
    }

    private function validateToken(Request $request, ?object $player): void
    {
        $playGame = $this->repository->getPlayGameByPlayIDToken(
            playID: $player->play_id,
            token: $request->externalToken
        );

        if (is_null($playGame) === true)
            throw new InvalidTokenException;
    }

    private function getPlayerDetails(Request $request): object
    {
        $playID = explode('_', $request->username)[1] ?? null;

        $player = $playID == null ? null : $this->repository->getPlayerByPlayID(playID: strtolower($playID));

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        return $player;
    }

    private function getPlayerBalance(ICredentials $credentials, string $playID): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($walletResponse['status_code'] !== 2100)
            throw new WalletErrorException;

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

        return $this->getPlayerBalance(credentials: $credentials, playID: $player->play_id);
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

        $playerBalance = $this->getPlayerBalance(credentials: $credentials, playID: $player->play_id);

        $transactionData = $this->repository->getTransactionByBetID(betID: $request->transactionCode);

        if (is_null($transactionData) === false)
            return $playerBalance;

        if ($playerBalance < (float) $request->amount)
            throw new InsufficientFundException;

        $this->validateToken(request: $request, player: $player);

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->transactionDate, self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                playID: $player->play_id,
                currency: $player->currency,
                gameCode: $request->gameCodeName,
                betID: $request->transactionCode,
                betAmount: (float) $request->amount,
                winAmount: 0,
                betTime: $transactionDate,
                status: 'WAGER',
                refID: $request->gameRoundCode
            );

            $report = $this->report->makeCasinoReport(
                trxID: $request->transactionCode,
                gameCode: $request->gameCodeName,
                betTime: $transactionDate,
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

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

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

        $betTransaction = $this->repository->getBetTransactionByRefID(refID: $request->gameRoundCode);

        if (is_null($betTransaction) === true)
            throw new ProviderTransactionNotFoundException(request: $request);

        $betID = is_null($request->pay) === true ? "L-{$request->requestId}" : $request->pay['transactionCode'];

        $transactionData = $this->repository->getTransactionByBetID(betID: $betID);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);
        $playerBalance = $this->getPlayerBalance(credentials: $credentials, playID: $player->play_id);

        if (is_null($transactionData) === false)
            return $playerBalance;

        if (is_null($request->pay) === true) {
            $this->repository->createTransaction(
                playID: $player->play_id,
                currency: $player->currency,
                gameCode: $request->gameCodeName,
                betID: $betID,
                betAmount: 0,
                winAmount: 0,
                betTime: Carbon::now()->setTimezone('GMT+8')->format('Y-m-d H:i:s'),
                status: 'PAYOUT',
                refID: $request->gameRoundCode
            );

            return $playerBalance;
        }

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->pay['transactionDate'], self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                playID: $player->play_id,
                currency: $player->currency,
                gameCode: $request->gameCodeName,
                betID: $betID,
                betAmount: 0,
                winAmount: (float) $request->pay['amount'],
                betTime: $transactionDate,
                status: 'PAYOUT',
                refID: $request->gameRoundCode,
            );

            $report = $this->report->makeCasinoReport(
                trxID: $request->pay['transactionCode'],
                gameCode: $request->gameCodeName,
                betTime: $transactionDate,
                betChoice: '-',
                result: '-'
            );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $player->play_id,
                currency: $player->currency,
                wagerTransactionID: "wagerPayout-{$betID}",
                wagerAmount: 0,
                payoutTransactionID: "wagerPayout-{$betID}",
                payoutAmount: (float) $request->pay['amount'],
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

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

        $betTransaction = $this->repository->getBetTransactionByBetID(
            betID: $request->pay['relatedTransactionCode']
        );

        if (is_null($betTransaction) === true)
            throw new RefundTransactionNotFoundException(request: $request);

        $refundTransaction = $this->repository->getTransactionByRefID(
            refID: $request->pay['relatedTransactionCode']
        );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if (is_null($refundTransaction) === false)
            return $this->getPlayerBalance(credentials: $credentials, playID: $player->play_id);

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->pay['transactionDate'], self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                playID: $player->play_id,
                currency: $player->currency,
                gameCode: $request->gameCodeName,
                betID: $request->pay['transactionCode'],
                betAmount: (float) $request->pay['amount'],
                winAmount: (float) $request->pay['amount'],
                betTime: $transactionDate,
                status: 'REFUND',
                refID: $request->pay['relatedTransactionCode']
            );  

            $walletResponse = $this->wallet->resettle(
                credentials: $credentials,
                playID: $player->play_id,
                currency: $player->currency,
                transactionID: "resettle-{$request->pay['transactionCode']}",
                amount: (float) $request->pay['amount'],
                betID: $request->pay['transactionCode'],
                settledTransactionID: "wager-{$request->pay['transactionCode']}",
                betTime: $transactionDate
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}

