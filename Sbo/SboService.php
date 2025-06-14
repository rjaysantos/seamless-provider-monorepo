<?php

namespace Providers\Sbo;

use Exception;
use Carbon\Carbon;
use Providers\Sbo\SboApi;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Sbo\SboRepository;
use Providers\Sbo\SboCredentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Sbo\Contracts\ICredentials;
use Providers\Sbo\Exceptions\WalletException;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Sbo\Exceptions\InsufficientFundException;
use Providers\Sbo\Exceptions\InvalidBetAmountException;
use Providers\Sbo\Exceptions\InvalidCompanyKeyException;
use Providers\Sbo\Exceptions\TransactionAlreadyVoidException;
use Providers\Sbo\Exceptions\TransactionAlreadyExistException;
use Providers\Sbo\Exceptions\InvalidTransactionStatusException;
use Providers\Sbo\SportsbookDetails\SboCancelSportsbookDetails;
use Providers\Sbo\SportsbookDetails\SboRunningSportsbookDetails;
use Providers\Sbo\Exceptions\TransactionAlreadyRollbackException;
use Providers\Sbo\SportsbookDetails\SboRollbackSportsbookDetails;
use Providers\Sbo\SportsbookDetails\SboSettleSportsbookDetails;
use Providers\Sbo\Exceptions\TransactionAlreadySettledException;
use Providers\Sbo\SportsbookDetails\SboSettleParlaySportsbookDetails;
use Providers\Sbo\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use Providers\Sbo\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class SboService
{
    const PROVIDER_TIMEZONE = 'GMT-4';
    const CASINO_MOBILE = 0;
    const SBO_MOBILE = 'm';
    const SBO_DESKTOP = 'd';
    const SBO_SPORTS_PRODUCTS = [1, 5];

    public function __construct(
        private SboRepository $repository,
        private SboCredentials $credentials,
        private SboApi $sboApi,
        private IWallet $wallet,
        private WalletReport $walletReport
    ) {}

    public function getLaunchUrl(Request $request): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $request->playId);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $username = "sbo_{$request->playId}";
        if (is_null($player) === true) {
            try {
                DB::connection('pgsql_write')->beginTransaction();

                $this->sboApi->registerPlayer(credentials: $credentials, username: $username);
                $this->repository->createPlayer(
                    playID: $request->playId,
                    currency: $request->currency,
                    ip: $request->memberIp
                );

                DB::connection('pgsql_write')->commit();
            } catch (\Exception $e) {
                DB::connection('pgsql_write')->rollBack();

                throw $e;
            }
        }

        $launchUrl = $this->sboApi->login(credentials: $credentials, username: $username);

        $device = $request->device == self::CASINO_MOBILE ? self::SBO_MOBILE : self::SBO_DESKTOP;

        return "https:{$launchUrl}&lang={$request->language}&oddstyle=ID&oddsmode=double&device={$device}";
    }

    public function getBetDetailUrl(Request $request): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $transaction = $this->repository->getTransactionByTrxID(trxID: $request->txn_id);

        if (is_null($transaction) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        return $this->sboApi->getBetPayload(credentials: $credentials, trxID: $request->txn_id);
    }

    private function getWalletBalance(ICredentials $credentials, string $playID): float
    {
        $balanceResponse = $this->wallet->balance($credentials, $playID);

        if ($balanceResponse['status_code'] != 2100)
            throw new WalletException;

        return $balanceResponse['credit'];
    }

    public function getBalance(Request $request): float
    {
        $playID = str_replace('sbo_', '', $request->Username);

        $player = $this->repository->getPlayerByPlayID(playID: $playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency($player->currency);

        if ($request->CompanyKey != $credentials->getCompanyKey())
            throw new InvalidCompanyKeyException;

        return $this->getWalletBalance(credentials: $credentials, playID: $playID);
    }

    public function deduct(Request $request): float
    {
        if (in_array($request->ProductType, self::SBO_SPORTS_PRODUCTS) === false)
            abort(404, 'Game not supported');

        $playID = Str::after($request->Username, 'sbo_');

        $playerDetails = $this->repository->getPlayerByPlayID(playID: $playID);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        if ($request->CompanyKey != $credentials->getCompanyKey())
            throw new InvalidCompanyKeyException;

        $balance = $this->getWalletBalance(credentials: $credentials, playID: $playID);

        if ($balance < $request->Amount)
            throw new InsufficientFundException(data: $balance);

        $transaction = $this->repository->getTransactionByTrxID(trxID: $request->TransferCode);

        if (is_null($transaction) === false)
            throw new TransactionAlreadyExistException(data: $balance);

        $transactionDate = Carbon::parse($request->BetTime, self::PROVIDER_TIMEZONE)
            ->setTimezone(8)
            ->format('Y-m-d H:i:s');

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $betID = "wager-1-{$request->TransferCode}";

            $sportsbookDetails = new SboRunningSportsbookDetails(gameCode: $request->GameId);

            $this->repository->createTransaction(
                betID: $betID,
                trxID: $request->TransferCode,
                playID: $playID,
                currency: $playerDetails->currency,
                betAmount: $request->Amount,
                payoutAmount: 0,
                betTime: $transactionDate,
                flag: 'running',
                sportsbookDetails: $sportsbookDetails
            );

            $sportsbookReports = $this->walletReport->makeSportsbookReport(
                trxID: $request->TransferCode,
                betTime: $transactionDate,
                sportsbookDetails: $sportsbookDetails
            );

            $walletResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $playID,
                currency: $playerDetails->currency,
                transactionID: $betID,
                amount: $request->Amount,
                report: $sportsbookReports
            );

            if ($walletResponse['status_code'] != 2100)
                throw new WalletException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function cancel(Request $request): float
    {
        $playID = Str::after($request->Username, 'sbo_');

        $playerData = $this->repository->getPlayerByPlayID(playID: $playID);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->CompanyKey != $credentials->getCompanyKey())
            throw new InvalidCompanyKeyException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->TransferCode);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException(data: $this->getWalletBalance(
                credentials: $credentials,
                playID: $playID
            ));

        if (trim($transactionData->flag) === 'void')
            throw new TransactionAlreadyVoidException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $settledTransactionID = $transactionData->bet_id;

            if (trim($transactionData->flag) === 'running') {
                $sportsbookDetails = new SboRunningSportsbookDetails(gameCode: $transactionData->game_code);

                $sportsbookReports = $this->walletReport->makeSportsbookReport(
                    trxID: $request->TransferCode,
                    betTime: $transactionData->bet_time,
                    sportsbookDetails: $sportsbookDetails
                );

                $settledTransactionID = "payout-1-{$request->TransferCode}";

                $balance = $this->wallet->payout(
                    credentials: $credentials,
                    playID: $playID,
                    currency: $playerData->currency,
                    transactionID: $settledTransactionID,
                    amount: 0.00,
                    report: $sportsbookReports
                );

                if ($balance['status_code'] != 2100)
                    throw new WalletException;
            }

            $voidedTransactionCount = $this->repository->getVoidedCount(trxID: $request->TransferCode) + 1;
            $betID = "cancel-{$voidedTransactionCount}-{$request->TransferCode}";

            $this->repository->inactiveTransaction(trxID: $request->TransferCode);

            $sportsbookDetails = new SboCancelSportsbookDetails(
                trxID: $request->TransferCode,
                ipAddress: $playerData->ip_address,
                transaction: $transactionData
            );

            $this->repository->createTransaction(
                betID: $betID,
                trxID: $request->TransferCode,
                playID: $transactionData->play_id,
                currency: $transactionData->currency,
                betAmount: $transactionData->bet_amount,
                payoutAmount: 0,
                betTime: $transactionData->bet_time,
                flag: 'void',
                sportsbookDetails: $sportsbookDetails
            );

            $balance = $this->wallet->resettle(
                credentials: $credentials,
                playID: $playID,
                currency: $playerData->currency,
                transactionID: $betID,
                amount: $transactionData->bet_amount - $transactionData->payout_amount,
                betID: $request->TransferCode,
                settledTransactionID: $settledTransactionID,
                betTime: $transactionData->bet_time
            );

            if ($balance['status_code'] != 2100)
                throw new WalletException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $balance['credit_after'];
    }

    public function rollback(Request $request): float
    {
        $playID = Str::after($request->Username, 'sbo_');

        $playerData = $this->repository->getPlayerByPlayID(playID: $playID);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->CompanyKey !== $credentials->getCompanyKey())
            throw new InvalidCompanyKeyException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->TransferCode);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException(data: $this->getWalletBalance(
                credentials: $credentials,
                playID: $playID
            ));

        if (trim($transactionData->flag) === 'rollback')
            throw new TransactionAlreadyRollbackException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $this->repository->inactiveTransaction(trxID: $request->TransferCode);

            $sportsbookDetails = new SboRollbackSportsbookDetails(transaction: $transactionData);

            $rollbackCount = $this->repository->getRollbackCount(trxID: $request->TransferCode) + 1;
            $betID = "rollback-{$rollbackCount}-{$request->TransferCode}";

            $this->repository->createTransaction(
                betID: $betID,
                trxID: $request->TransferCode,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                betAmount: $transactionData->bet_amount,
                payoutAmount: 0,
                betTime: $transactionData->bet_time,
                flag: 'rollback',
                sportsbookDetails: $sportsbookDetails
            );

            if (trim($transactionData->flag) === 'settled')
                $resettleAmount = $transactionData->payout_amount;
            else
                $resettleAmount = $transactionData->bet_amount - $transactionData->payout_amount;

            $walletResponse = $this->wallet->resettle(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                transactionID: $betID,
                amount: -$resettleAmount,
                betID: $request->TransferCode,
                settledTransactionID: $transactionData->bet_id,
                betTime: $transactionData->bet_time
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    private function isMixParlay(object $betDetails): bool
    {
        return ($betDetails->sportsType ?? null) === 'Mix Parlay'
            || ($betDetails->productType ?? null) === 'MixParlayDesktop';
    }

    public function settle(Request $request): float
    {
        $playID = Str::after($request->Username, 'sbo_');

        $playerData = $this->repository->getPlayerByPlayID(playID: $playID);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->CompanyKey !== $credentials->getCompanyKey())
            throw new InvalidCompanyKeyException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->TransferCode);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException(data: $this->getWalletBalance(
                credentials: $credentials,
                playID: $playID
            ));

        match (trim($transactionData->flag)) {
            'settled' => throw new TransactionAlreadySettledException(data: $this->getWalletBalance(
                credentials: $credentials,
                playID: $playID
            )),
            'void' => throw new TransactionAlreadyVoidException,
            default => null,
        };

        $betDetails = $this->sboApi->getBetList(credentials: $credentials, trxID: $request->TransferCode);

        if ($this->isMixParlay(betDetails: $betDetails) === true) {
            $sportsbookDetails = new SboSettleParlaySportsbookDetails(
                request: $request,
                betAmount: $transactionData->bet_amount,
                odds: $betDetails->odds,
                oddsStyle: $betDetails->oddsStyle,
                ipAddress: $playerData->ip_address
            );
        } else {
            $sportsbookDetails = new SboSettleSportsbookDetails(
                betDetails: $betDetails,
                request: $request,
                betAmount: $transactionData->bet_amount,
                ipAddress: $playerData->ip_address
            );
        }

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->ResultTime, self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->inactiveTransaction(trxID: $request->TransferCode);

            if (trim($transactionData->flag) === 'rollback') {
                $resettleCount = $this->repository->getRollbackCount(trxID: $request->TransferCode);
                $betID = "resettle-{$resettleCount}-{$request->TransferCode}";
                $betTime = $transactionData->bet_time;

                $walletResponse = $this->wallet->resettle(
                    credentials: $credentials,
                    playID: $playerData->play_id,
                    currency: $playerData->currency,
                    transactionID: $betID,
                    amount: $request->WinLoss,
                    betID: $request->TransferCode,
                    settledTransactionID: $transactionData->bet_id,
                    betTime: $transactionDate
                );
            } else {
                $settleCount = $this->repository->getSettleCount(trxID: $request->TransferCode) + 1;
                $betID = "payout-{$settleCount}-{$request->TransferCode}";
                $betTime = $transactionDate;

                $report = $this->walletReport->makeSportsbookReport(
                    trxID: $request->TransferCode,
                    betTime: $transactionDate,
                    sportsbookDetails: $sportsbookDetails
                );

                $walletResponse = $this->wallet->payout(
                    credentials: $credentials,
                    playID: $playerData->play_id,
                    currency: $playerData->currency,
                    transactionID: $betID,
                    amount: $request->WinLoss,
                    report: $report
                );
            }

            $this->repository->createTransaction(
                betID: $betID,
                trxID: $request->TransferCode,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                betAmount: $transactionData->bet_amount,
                payoutAmount: $request->WinLoss,
                betTime: $betTime,
                flag: 'settled',
                sportsbookDetails: $sportsbookDetails
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
