<?php

namespace Providers\Ors;

use Exception;
use Carbon\Carbon;
use Providers\Ors\OrsApi;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Ors\OgSignature;
use Providers\Ors\OrsRepository;
use Providers\Ors\OrsCredentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Ors\Contracts\ICredentials;
use Providers\Ors\Exceptions\WalletErrorException;
use Providers\Ors\Exceptions\InvalidTokenException;
use Providers\Ors\Exceptions\InsufficientFundException;
use Providers\Ors\Exceptions\InvalidPublicKeyException;
use Providers\Ors\Exceptions\InvalidSignatureException;
use Providers\Ors\Exceptions\TransactionAlreadyExistsException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Ors\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use Providers\Ors\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class OrsService
{
    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public function __construct(
        private OrsRepository $repository,
        private OrsCredentials $credentials,
        private OrsApi $api,
        private OgSignature $encryption,
        private IWallet $wallet,
        private WalletReport $report
    ) {}

    public function getLaunchUrl(Request $request): string
    {
        $playerData = $this->repository->getPlayerByPlayID($request->playId);

        if (is_null($playerData) === true)
            $this->repository->createPlayer(
                playID: $request->playId,
                username: $request->username,
                currency: $request->currency
            );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $token = $this->repository->createToken(playID: $request->playId);

        return $this->api->enterGame(credentials: $credentials, request: $request, token: $token);
    }

    public function getBetDetailUrl(Request $request): string
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($playerData) === true)
            throw new CasinoPlayerNotFoundException;

        $transactionData = $this->repository->getTransactionByExtID(extID: $request->bet_id);

        if (is_null($transactionData) === true)
            throw new CasinoTransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $extID = Str::after($request->bet_id, '-');

        return $this->api->getBettingRecords(
            credentials: $credentials,
            transactionID: $extID,
            playID: $request->play_id
        );
    }

    private function verifyPlayerAccess(Request $request, ICredentials $credentials): void
    {
        if ($request->header('key') !== $credentials->getPublicKey())
            throw new InvalidPublicKeyException;

        if ($this->encryption->isSignatureValid(request: $request, credentials: $credentials) === false)
            throw new InvalidSignatureException;
    }

    private function getPlayerDetails(Request $request): object
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->player_id);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        return $playerData;
    }

    private function getBalanceFromWallet(ICredentials $credentials, string $playID): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($walletResponse['status_code'] !== 2100)
            throw new WalletErrorException;

        return $walletResponse['credit'];
    }

    public function authenticate(Request $request): void
    {
        $playerData = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $this->verifyPlayerAccess(request: $request, credentials: $credentials);

        $playGame = $this->repository->getPlayGameByPlayIDToken(playID: $request->player_id, token: $request->token);

        if (is_null($playGame) === true)
            throw new InvalidTokenException;
    }

    public function getBalance(Request $request): object
    {
        $playerData = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $this->verifyPlayerAccess(request: $request, credentials: $credentials);

        $balance = $this->getBalanceFromWallet(credentials: $credentials, playID: $request->player_id);

        return (object) [
            'balance' => $balance,
            'currency' => $playerData->currency
        ];
    }

    public function bet(Request $request): float
    {
        $playerData = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $this->verifyPlayerAccess(request: $request, credentials: $credentials);

        $balance = $this->getBalanceFromWallet(credentials: $credentials, playID: $request->player_id);

        if ($request->total_amount > $balance)
            throw new InsufficientFundException;

        foreach ($request->records as $record) {
            $transactionData = $this->repository->getTransactionByExtID(extID: "wager-{$record['transaction_id']}");

            if (is_null($transactionData) === false)
                throw new TransactionAlreadyExistsException;
        }

        foreach ($request->records as $record) {
            try {
                DB::connection('pgsql_report_write')->beginTransaction();

                $betTime = Carbon::parse($request->called_at, self::PROVIDER_API_TIMEZONE)
                    ->setTimezone('GMT+8')
                    ->format('Y-m-d H:i:s');

                $this->repository->createTransaction(
                    extID: "wager-{$record['transaction_id']}",
                    roundID: $record['transaction_id'],
                    playID: $request->player_id,
                    username: $playerData->username,
                    currency: $playerData->currency,
                    gameCode: $request->game_id,
                    betAmount: $record['amount'],
                    betWinlose: 0,
                    transactionDate: $betTime,
                );

                if (in_array($request->game_id, $credentials->getArcadeGameList()) === true)
                    $report = $this->report->makeArcadeReport(
                        transactionID: $record['transaction_id'],
                        gameCode: $request->game_id,
                        betTime: $betTime
                    );
                else
                    $report = $this->report->makeSlotReport(
                        transactionID: $record['transaction_id'],
                        gameCode: $request->game_id,
                        betTime: $betTime
                    );

                $walletResponse = $this->wallet->wager(
                    credentials: $credentials,
                    playID: $request->player_id,
                    currency: $playerData->currency,
                    transactionID: "wager-{$record['transaction_id']}",
                    amount: $record['amount'],
                    report: $report
                );

                if ($walletResponse['status_code'] !== 2100)
                    throw new WalletErrorException;

                DB::connection('pgsql_report_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_report_write')->rollBack();
                throw $e;
            }
        }

        return $walletResponse['credit_after'];
    }

    public function rollback(Request $request): float
    {
        $playerData = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $this->verifyPlayerAccess(request: $request, credentials: $credentials);

        foreach ($request->records as $record) {
            $betTransaction = $this->repository->getTransactionByExtID(extID: "wager-{$record['transaction_id']}");

            if (is_null($betTransaction) === true)
                throw new ProviderTransactionNotFoundException;
        }

        foreach ($request->records as $record) {
            try {
                DB::connection('pgsql_report_write')->beginTransaction();

                $transactionDate = Carbon::parse($request->called_at, self::PROVIDER_API_TIMEZONE)
                    ->setTimezone('GMT+8')
                    ->format('Y-m-d H:i:s');

                $this->repository->createTransaction(
                    extID: "cancel-{$record['transaction_id']}",
                    roundID: $record['transaction_id'],
                    playID: $request->player_id,
                    username: $playerData->username,
                    currency: $playerData->currency,
                    gameCode: $request->game_id,
                    betAmount: -$record['amount'],
                    betWinlose: 0,
                    transactionDate: $transactionDate
                );

                $walletResponse = $this->wallet->cancel(
                    credentials: $credentials,
                    transactionID: "cancelBet-{$record['transaction_id']}",
                    amount: $record['amount'],
                    transactionIDToCancel: "wager-{$record['transaction_id']}"
                );

                if ($walletResponse['status_code'] !== 2100)
                    throw new WalletErrorException;

                DB::connection('pgsql_report_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_report_write')->rollBack();
                throw $e;
            }
        }

        return $walletResponse['credit_after'];
    }

    public function settle(Request $request): float
    {
        $playerData = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $this->verifyPlayerAccess(request: $request, credentials: $credentials);

        $betTransaction = $this->repository->getTransactionByExtID(extID: "wager-{$request->transaction_id}");

        if (is_null($betTransaction) === true)
            throw new ProviderTransactionNotFoundException;

        $settleTransaction = $this->repository->getTransactionByExtID(extID: "payout-{$request->transaction_id}");

        if (is_null($settleTransaction) === false)
            return $this->getBalanceFromWallet(credentials: $credentials, playID: $request->player_id);

        try {
            DB::connection('pgsql_report_write')->beginTransaction();

            $settleTime = Carbon::createFromTimestamp($request->called_at, self::PROVIDER_API_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                extID: "payout-{$request->transaction_id}",
                roundID: $request->transaction_id,
                playID: $request->player_id,
                username: $playerData->username,
                currency: $playerData->currency,
                gameCode: $request->game_id,
                betAmount: 0,
                betWinlose: $request->amount - $betTransaction->bet_amount,
                transactionDate: $settleTime,
            );

            if (in_array($request->game_id, $credentials->getArcadeGameList()) === true)
                $report = $this->report->makeArcadeReport(
                    transactionID: $request->transaction_id,
                    gameCode: $request->game_id,
                    betTime: $settleTime
                );
            else
                $report = $this->report->makeSlotReport(
                    transactionID: $request->transaction_id,
                    gameCode: $request->game_id,
                    betTime: $settleTime
                );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $request->player_id,
                currency: $playerData->currency,
                transactionID: "payout-{$request->transaction_id}",
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_report_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_report_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function bonus(Request $request): float
    {
        $playerData = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $this->verifyPlayerAccess(request: $request, credentials: $credentials);

        $transactionData = $this->repository->getTransactionByExtID(extID: "bonus-{$request->transaction_id}");

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistsException;

        try {
            DB::connection('pgsql_report_write')->beginTransaction();

            $bonusTime = Carbon::createFromTimestamp($request->called_at, self::PROVIDER_API_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                extID: "bonus-{$request->transaction_id}",
                roundID: $request->transaction_id,
                playID: $request->player_id,
                username: $playerData->username,
                currency: $playerData->currency,
                gameCode: $request->game_code,
                betAmount: 0,
                betWinlose: $request->amount,
                transactionDate: $bonusTime,
            );

            $report = $this->report->makeBonusReport(
                transactionID: $request->transaction_id,
                gameCode: $request->game_code,
                betTime: $bonusTime
            );

            $walletResponse = $this->wallet->bonus(
                credentials: $credentials,
                playID: $request->player_id,
                currency: $playerData->currency,
                transactionID: "bonus-{$request->transaction_id}",
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_report_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_report_write')->rollBack();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
