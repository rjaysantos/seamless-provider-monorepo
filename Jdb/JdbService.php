<?php

namespace Providers\Jdb;

use Exception;
use Carbon\Carbon;
use Providers\Jdb\JdbApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Jdb\JdbRepository;
use Providers\Jdb\JdbCredentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Jdb\Contracts\ICredentials;
use App\Exceptions\Casino\WalletErrorException;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Jdb\Exceptions\InsufficientFundException;
use Providers\Jdb\Exceptions\TransactionAlreadyExistException;
use Providers\Jdb\Exceptions\TransactionAlreadySettledException;
use Providers\Jdb\Exceptions\TransactionStillProcessingException;
use Providers\Jdb\Exceptions\WalletErrorException as ProviderWalletErrorException;
use Providers\Jdb\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use Providers\Jdb\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class JdbService
{
    private const PROVIDER_TIMEZONE = 'GMT+8';
    private const SLOT_GAMECODE = 0;

    public function __construct(
        private JdbRepository $repository,
        private JdbCredentials $credentials,
        private JdbApi $api,
        private IWallet $wallet,
        private WalletReport $report
    ) {
    }

    public function getLaunchUrl(Request $request): string
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->playId);

        if (is_null($playerData) === true)
            $this->repository->createPlayer(
                playID: $request->playId,
                username: $request->username,
                currency: $request->currency
            );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $balanceResponse = $this->wallet->balance(
            credentials: $credentials,
            playID: $request->playId
        );

        if ($balanceResponse['status_code'] !== 2100)
            throw new WalletErrorException;

        return $this->api->getGameLaunchUrl(
            credentials: $credentials,
            request: $request,
            balance: $balanceResponse['credit']
        );
    }

    public function getBetDetailUrl(Request $request): string
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($playerData) === true)
            throw new PlayerNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->bet_id);

        if (is_null($transactionData) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        return $this->api->queryGameResult(
            credentials: $credentials,
            playID: $request->play_id,
            historyID: $transactionData->history_id,
            gameID: $request->game_id
        );
    }

    private function getPlayerBalance(
        ICredentials $credentials,
        string $playID
    ): float {
        $balanceResponse = $this->wallet->balance(
            credentials: $credentials,
            playID: $playID
        );

        if ($balanceResponse['status_code'] != 2100)
            throw new ProviderWalletErrorException;

        return $balanceResponse['credit'];
    }

    public function getBalance(object $request): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->uid);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        return $this->getPlayerBalance(
            credentials: $credentials,
            playID: $request->uid
        );
    }

    public function betAndSettle(object $request): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->uid);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->transferId);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $balance = $this->getPlayerBalance(
            credentials: $credentials,
            playID: $request->uid
        );

        $betAmount = abs($request->bet);

        if ($balance < $betAmount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::createFromTimestamp($request->ts / 1000, self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createSettleTransaction(
                transactionID: $request->transferId,
                betAmount: $betAmount,
                winAmount: $request->win,
                transactionDate: $transactionDate,
                historyID: $request->historyId
            );

            if ($request->gType !== self::SLOT_GAMECODE) {
                $report = $this->report->makeArcadeReport(
                    transactionID: $request->transferId,
                    gameCode: "{$request->gType}-{$request->mType}",
                    betTime: $transactionDate
                );
            } else {
                $report = $this->report->makeSlotReport(
                    transactionID: $request->transferId,
                    gameCode: $request->mType,
                    betTime: $transactionDate
                );
            }

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $request->uid,
                currency: $request->currency,
                wagerTransactionID: "wagerpayout-{$request->transferId}",
                wagerAmount: $betAmount,
                payoutTransactionID: "wagerpayout-{$request->transferId}",
                payoutAmount: $request->win,
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function cancelBetAndSettle(object $request): void
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->uid);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->transferId);

        if (is_null($transactionData) === true)
            throw new TransactionStillProcessingException;
        
        throw new TransactionAlreadySettledException;
    }

    public function bet(object $request): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->uid);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->transferId);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $balance = $this->getPlayerBalance(
            credentials: $credentials,
            playID: $request->uid
        );

        if ($balance < $request->amount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::createFromTimestamp($request->ts / 1000, self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createBetTransaction(
                transactionID: $request->transferId,
                betAmount: $request->amount,
                betTime: $transactionDate
            );

            if ($request->gType !== self::SLOT_GAMECODE) {
                $report = $this->report->makeArcadeReport(
                    transactionID: $request->transferId,
                    gameCode: "{$request->gType}-{$request->mType}",
                    betTime: $transactionDate
                );
            } else {
                $report = $this->report->makeSlotReport(
                    transactionID: $request->transferId,
                    gameCode: $request->mType,
                    betTime: $transactionDate
                );
            }

            $walletResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $request->uid,
                currency: $request->currency,
                transactionID: "wager-{$request->transferId}",
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function cancelBet(object $request): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->uid);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->refTransferIds[0]);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        if (is_null($transactionData->updated_at) === false)
            return $this->getPlayerBalance(
                credentials: $credentials,
                playID: $request->uid
            );

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::createFromTimestamp($request->ts / 1000, self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->cancelBetTransaction(
                transactionID: $request->refTransferIds[0],
                cancelTime: $transactionDate
            );

            $walletResponse = $this->wallet->cancel(
                credentials: $credentials,
                transactionID: "cancel-{$request->refTransferIds[0]}",
                amount: $request->amount,
                transactionIDToCancel: "wager-{$request->refTransferIds[0]}",
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function settle(object $request): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->uid);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->refTransferIds[0]);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        if (is_null($transactionData->updated_at) === false)
            return $this->getPlayerBalance(
                credentials: $credentials,
                playID: $request->uid
            );

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::createFromTimestamp($request->ts / 1000, self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->settleBetTransaction(
                transactionID: $request->refTransferIds[0],
                historyID: $request->historyId,
                winAmount: $request->amount,
                settleTime: $transactionDate
            );

            if ($request->gType !== self::SLOT_GAMECODE) {
                $report = $this->report->makeArcadeReport(
                    transactionID: $request->refTransferIds[0],
                    gameCode: "{$request->gType}-{$request->mType}",
                    betTime: $transactionDate
                );
            } else {
                $report = $this->report->makeSlotReport(
                    transactionID: $request->refTransferIds[0],
                    gameCode: $request->mType,
                    betTime: $transactionDate
                );
            }

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $request->uid,
                currency: $request->currency,
                transactionID: "payout-{$request->refTransferIds[0]}",
                amount: $request->amount,
                report: $report,
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}