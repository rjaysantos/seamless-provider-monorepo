<?php

namespace Providers\Red;

use Exception;
use Carbon\Carbon;
use Providers\Red\RedApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Red\RedRepository;
use Providers\Red\RedCredentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Red\Contracts\ICredentials;
use App\Exceptions\Casino\WalletErrorException;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Red\Exceptions\InsufficientFundException;
use Providers\Red\Exceptions\InvalidSecretKeyException;
use Providers\Red\Exceptions\BonusTransactionAlreadyExists;
use Providers\Red\Exceptions\TransactionDoesNotExistException;
use Providers\Red\Exceptions\TransactionAlreadyExistsException;
use Providers\Red\Exceptions\TransactionAlreadySettledException;
use Providers\Red\Exceptions\WalletErrorException as ProviderWalletErrorException;
use Providers\Red\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;

class RedService
{
    private const PROVIDER_API_TIMEZONE = 'GMT+0';

    public function __construct(
        private RedRepository $repository,
        private RedCredentials $credentials,
        private RedApi $api,
        private IWallet $wallet,
        private WalletReport $walletReport
    ) {
    }

    public function getLaunchUrl(Request $request): string
    {
        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $balanceResponse = $this->wallet->balance(
            credentials: $credentials,
            playID: $request->playId
        );

        if ($balanceResponse['status_code'] !== 2100)
            throw new WalletErrorException;

        $playerData = $this->repository->getPlayerByPlayID(playID: $request->playId);

        $apiResponse = $this->api->authenticate(
            credentials: $credentials,
            request: $request,
            username: is_null($playerData) === true ? $request->playId : $playerData->username,
            balance: $balanceResponse['credit']
        );

        if (is_null($playerData) === true)
            $this->repository->createPlayer(
                playID: $request->playId,
                currency: $request->currency,
                userIDProvider: $apiResponse->userID
            );

        return $apiResponse->launchUrl;
    }

    public function getBetDetailUrl(Request $request): string
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($playerData) === true)
            throw new PlayerNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->bet_id);

        if (is_null($transactionData) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        return $this->api->getBetResult(
            credentials: $credentials,
            transactionID: $request->bet_id
        );
    }

    private function getPlayerBalance(ICredentials $credentials, string $playID): float
    {
        $balanceResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($balanceResponse['status_code'] != 2100)
            throw new ProviderWalletErrorException;

        return $balanceResponse['credit'];
    }

    private function getPlayerDataByUserIDProvider(string $userID): object
    {
        $player = $this->repository->getPlayerByUserIDProvider(userIDProvider: $userID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        return $player;
    }

    public function getBalance(Request $request): float
    {
        $playerData = $this->getPlayerDataByUserIDProvider(userID: $request->user_id);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->header('secret-key') != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        return $this->getPlayerBalance(
            credentials: $credentials,
            playID: $playerData->play_id
        );
    }

    public function bet(Request $request): float
    {
        $playerData = $this->getPlayerDataByUserIDProvider(userID: $request->user_id);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->header('secret-key') != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $extID = "wager-{$request->txn_id}";

        $transactionData = $this->repository->getTransactionByTrxID(extID: $extID);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistsException;

        $balance = $this->getPlayerBalance(credentials: $credentials, playID: $playerData->play_id);

        if ($balance < $request->amount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_report_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->debit_time, self::PROVIDER_API_TIMEZONE)
                ->setTimezone(8)
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                extID: $extID,
                playID: $playerData->play_id,
                username: $playerData->username,
                currency: $playerData->currency,
                gameCode: $request->game_id,
                betAmount: $request->amount,
                betWinlose: 0,
                transactionDate: $transactionDate
            );

            $report = $this->walletReport->makeSlotReport(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: $transactionDate
            );

            $walletResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                transactionID: $extID,
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_report_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_report_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function settle(Request $request): float
    {
        $playerData = $this->getPlayerDataByUserIDProvider(userID: $request->user_id);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->header('secret-key') != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $betTransactionData = $this->repository->getTransactionByTrxID(extID: "wager-{$request->txn_id}");

        if (is_null($betTransactionData) === true)
            throw new TransactionDoesNotExistException;

        $extID = "payout-{$request->txn_id}";

        $transactionData = $this->repository->getTransactionByTrxID(extID: $extID);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadySettledException;

        try {
            DB::connection('pgsql_report_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->credit_time, self::PROVIDER_API_TIMEZONE)
                ->setTimezone(8)
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                extID: $extID,
                playID: $betTransactionData->play_id,
                username: $betTransactionData->username,
                currency: $betTransactionData->currency,
                gameCode: $betTransactionData->game_code,
                betAmount: 0,
                betWinlose: $betTransactionData->bet_amount - $request->amount,
                transactionDate: $transactionDate
            );

            $report = $this->walletReport->makeSlotReport(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: $transactionDate
            );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                transactionID: $extID,
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_report_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_report_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function bonus(Request $request): float
    {
        $playerData = $this->getPlayerDataByUserIDProvider(userID: $request->user_id);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->header('secret-key') != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $extID = "bonus-{$request->txn_id}";

        $transactionData = $this->repository->getTransactionByTrxID(extID: $extID);
    
        if (is_null($transactionData) === false)
            throw new BonusTransactionAlreadyExists;

        try {
            DB::connection('pgsql_report_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->credit_time, self::PROVIDER_API_TIMEZONE)
                ->setTimezone(8)
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                extID: $extID,
                playID: $playerData->play_id,
                username: $playerData->username,
                currency: $playerData->currency,
                gameCode: $request->game_id,
                betAmount: 0,
                betWinlose: $request->amount,
                transactionDate: $transactionDate
            );

            $report = $this->walletReport->makeBonusReport(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: $transactionDate
            );

            $walletResponse = $this->wallet->bonus(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                transactionID: $extID,
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_report_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_report_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
