<?php

namespace Providers\Gs5;

use Exception;
use Carbon\Carbon;
use Providers\Gs5\Gs5Api;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Providers\Gs5\Gs5Repository;
use Providers\Gs5\Gs5Credentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Gs5\Contracts\ICredentials;
use App\Exceptions\Casino\PlayerNotFoundException;
use Providers\Gs5\Exceptions\WalletErrorException;
use Providers\Gs5\Exceptions\TokenNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Gs5\Exceptions\TransactionAlreadySettledException;
use Providers\Gs5\Exceptions\InsufficientFundException;
use Providers\Gs5\Exceptions\ProviderWalletErrorException;
use Providers\Gs5\Exceptions\TransactionAlreadyExistsException;
use Providers\Gs5\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class Gs5Service
{
    private const PROVIDER_CURRENCY_CONVERSION = 100;
    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public function __construct(
        private Gs5Repository $repository,
        private Gs5Credentials $credentials,
        private Gs5Api $api,
        private IWallet $wallet,
        private WalletReport $report,
        private Randomizer $randomizer
    ) {
    }

    private function getPlayerBalance(ICredentials $credentials, string $playID): float
    {
        $balanceResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($balanceResponse['status_code'] !== 2100)
            throw new WalletErrorException;

        return $balanceResponse['credit'];
    }

    public function getBalance(Request $request): float
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->access_token);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $balance = $this->getPlayerBalance(credentials: $credentials, playID: $playerData->play_id);

        return $balance * self::PROVIDER_CURRENCY_CONVERSION;
    }

    public function authenticate(Request $request): object
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->access_token);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $balance = $this->getPlayerBalance(credentials: $credentials, playID: $playerData->play_id);

        return (object) [
            'member_id' => $playerData->play_id,
            'member_name' => $playerData->username,
            'balance' => $balance * self::PROVIDER_CURRENCY_CONVERSION
        ];
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

        $token = $this->randomizer->createToken();

        $this->repository->createOrUpdatePlayGame(playID: $request->playId, token: $token);

        return $this->api->getLaunchUrl(
            credentials: $credentials,
            playerToken: $token,
            gameID: $request->gameId,
            lang: $request->language
        );
    }

    public function cancel(Request $request): float
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->access_token);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->txn_id);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException;

        if (is_null($transactionData->updated_at) === false)
            throw new TransactionAlreadySettledException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $this->repository->settleTransaction(
                trxID: $request->txn_id,
                winAmount: $transactionData->bet_amount,
                settleTime: $transactionData->created_at
            );

            $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

            $walletResponse = $this->wallet->cancel(
                credentials: $credentials,
                transactionID: "cancel-{$request->txn_id}",
                amount: $transactionData->bet_amount,
                transactionIDToCancel: "wager-{$request->txn_id}"
            );

            if ($walletResponse['status_code'] != 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw new $e;
        }

        return $walletResponse['credit_after'] * self::PROVIDER_CURRENCY_CONVERSION;
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

        return $this->api->getGameHistory(credentials: $credentials, trxID: $request->bet_id);
    }

    public function bet(Request $request): float
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->access_token);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->txn_id);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistsException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $balance = $this->getPlayerBalance(credentials: $credentials, playID: $playerData->play_id);

        $betAmount = $request->total_bet / self::PROVIDER_CURRENCY_CONVERSION;

        if ($balance < $betAmount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::createFromTimestamp($request->ts, self::PROVIDER_API_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createWagerTransaction(
                trxID: $request->txn_id,
                betAmount: $betAmount,
                transactionDate: $transactionDate
            );

            $report = $this->report->makeSlotReport(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: $transactionDate
            );

            $walletResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                transactionID: "wager-{$request->txn_id}",
                amount: $betAmount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'] * self::PROVIDER_CURRENCY_CONVERSION;
    }

    public function settle(Request $request): float
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->access_token);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->txn_id);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException;

        if (is_null($transactionData->updated_at) === false)
            throw new TransactionAlreadySettledException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::createFromTimestamp($request->ts, self::PROVIDER_API_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $winAmount = $request->total_win / self::PROVIDER_CURRENCY_CONVERSION;

            $this->repository->settleTransaction(
                trxID: $request->txn_id,
                winAmount: $winAmount,
                settleTime: $transactionDate
            );

            $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

            $report = $this->report->makeSlotReport(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: $transactionDate
            );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                transactionID: "payout-{$request->txn_id}",
                amount: $winAmount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'] * self::PROVIDER_CURRENCY_CONVERSION;
    }
}