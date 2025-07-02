<?php

namespace Providers\Pla;

use Exception;
use Carbon\Carbon;
use Providers\Pla\PlaApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use App\Libraries\Randomizer;
use Providers\Pla\PlaRepository;
use Providers\Pla\PlaCredentials;
use Illuminate\Support\Facades\DB;
use Providers\Pla\DTO\PlaRequestDTO;
use Wallet\V1\ProvSys\Transfer\Report;
use Providers\Pla\DTO\PlaTransactionDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Pla\Contracts\ICredentials;
use Providers\Pla\Exceptions\WalletErrorException;
use Providers\Pla\Exceptions\InvalidTokenException;
use Providers\Pla\Exceptions\InsufficientFundException;
use Providers\Pla\Exceptions\RefundTransactionNotFoundException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Pla\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use Providers\Pla\DTO\PlaPlayerDTO;
use Providers\Pla\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class PlaService
{
    private const PROVIDER_TIMEZONE = 'GMT+0';

    public function __construct(
        private PlaRepository $repository,
        private PlaCredentials $credentials,
        private PlaApi $api,
        private Randomizer $randomizer,
        private IWallet $wallet,
        private WalletReport $report,
    ) {}

    public function getLaunchUrl(CasinoRequestDTO $casinoRequest): string
    {
        $player = PlaPlayerDTO::fromPlayRequest(casinoRequest: $casinoRequest);

        $this->repository->createOrUpdatePlayer(playerDTO: $player);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        return $this->api->getGameLaunchUrl(credentials: $credentials, requestDTO: $casinoRequest, playerDTO: $player);
    }

    public function getBetDetail(Request $request): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($player) === true)
            throw new CasinoPlayerNotFoundException;

        $transaction = $this->repository->getTransactionByTrxID(trxID: $request->bet_id);

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
            throw new InvalidTokenException(request: $request);
    }

    private function getPlayerDetails(PlaRequestDTO $requestDTO): object
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException(requestDTO: $requestDTO);

        return $player;
    }

    private function getPlayerBalance(ICredentials $credentials, PlaRequestDTO $requestDTO): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $requestDTO->playID);

        if ($walletResponse['status_code'] !== 2100)
            throw new WalletErrorException(requestDTO: $requestDTO);

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

    private function makeReport(
        ICredentials $credentials,
        string $transactionID,
        PlaTransactionDTO $transactionDTO
    ): Report {
        if (in_array($transactionDTO->gameID, $credentials->getArcadeGameList()) === true)
            return $this->report->makeArcadeReport(
                transactionID: $transactionID,
                gameCode: $transactionDTO->gameID,
                betTime: $transactionDTO->dateTime
            );
        else
            return $this->report->makeSlotReport(
                transactionID: $transactionID,
                gameCode: $transactionDTO->gameID,
                betTime: $transactionDTO->dateTime
            );
    }

    public function bet(Request $request): float
    {
        $player = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);
        $playerBalance = $this->getPlayerBalance(credentials: $credentials, request: $request, playID: $player->play_id);

        $transaction = $this->repository->getTransactionByTrxID(trxID: $request->transactionCode);

        if (is_null($transaction) === false)
            return $playerBalance;

        if ($playerBalance < (float) $request->amount)
            throw new InsufficientFundException(request: $request);

        $this->validateToken(request: $request, player: $player);

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->transactionDate, self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                trxID: $request->transactionCode,
                betAmount: (float) $request->amount,
                winAmount: 0,
                betTime: $transactionDate,
                settleTime: null,
                refID: $request->gameRoundCode
            );

            $report = $this->makeReport(
                credentials: $credentials,
                transactionID: $request->transactionCode,
                gameCode: $request->gameCodeName,
                betTime: $transactionDate
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
                throw new WalletErrorException($request);

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function settle(PlaRequestDTO $requestDTO): float
    {
        $player = $this->getPlayerDetails(requestDTO: $requestDTO);

        $roundTransaction = $this->repository->getTransactionByRoundID(roundID: $requestDTO->roundID);

        if (is_null($roundTransaction) === true)
            throw new ProviderTransactionNotFoundException(requestDTO: $requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);
        $balance = $this->getPlayerBalance(credentials: $credentials, requestDTO: $requestDTO);

        $transactionDTO = PlaTransactionDTO::wagerAndPayout(
            extID: "wagerPayout-{$requestDTO->transactionID}",
            requestDTO: $requestDTO,
            playerDTO: $player
        );

        $existingTransaction = $this->repository->getTransactionByExtID(extID: $transactionDTO->extID);

        if (is_null($existingTransaction) === false)
            return $balance;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $transactionDTO);

            if ($requestDTO->transactionType === 'LOSE') {
                $this->repository->commit();
                return $balance;
            }

            $report = $this->makeReport(
                credentials: $credentials,
                transactionID: $requestDTO->transactionID,
                transactionDTO: $transactionDTO
            );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $player->playID,
                currency: $player->currency,
                wagerTransactionID: $transactionDTO->extID,
                wagerAmount: $transactionDTO->betAmount,
                payoutTransactionID: $transactionDTO->extID,
                payoutAmount: $transactionDTO->winAmount,
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException(requestDTO: $requestDTO);

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function refund(Request $request): float
    {
        $player = $this->getPlayerDetails($request);

        $betTransaction = $this->repository->getBetTransactionByTrxID(trxID: $request->pay['relatedTransactionCode']);

        if (is_null($betTransaction) === true)
            throw new RefundTransactionNotFoundException(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $refundTransaction = $this->repository->getTransactionByRefID(refID: $request->pay['relatedTransactionCode']);

        if (is_null($refundTransaction) === false)
            return $this->getPlayerBalance(credentials: $credentials, request: $request, playID: $player->play_id);

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->pay['transactionDate'], self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                trxID: $request->pay['transactionCode'],
                betAmount: (float) $request->pay['amount'],
                winAmount: (float) $request->pay['amount'],
                betTime: $transactionDate,
                settleTime: $transactionDate,
                refID: $request->pay['relatedTransactionCode']
            );

            $report = $this->makeReport(
                credentials: $credentials,
                transactionID: $request->pay['transactionCode'],
                gameCode: $request->gameCodeName,
                betTime: $transactionDate
            );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $player->play_id,
                currency: $player->currency,
                wagerTransactionID: "wagerPayout-{$request->pay['transactionCode']}",
                wagerAmount: 0,
                payoutTransactionID: "wagerPayout-{$request->pay['transactionCode']}",
                payoutAmount: (float) $request->pay['amount'],
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException($request);

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
