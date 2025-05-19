<?php

namespace Providers\Sbo;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Providers\Sbo\SboApi;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Sbo\SboRepository;
use Providers\Sbo\SboCredentials;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Sbo\Contracts\ICredentials;
use Providers\Sbo\Exceptions\WalletException;
use Providers\Sbo\Exceptions\InsufficientFundException;
use Providers\Sbo\Exceptions\InvalidBetAmountException;
use Providers\Sbo\Exceptions\InvalidCompanyKeyException;
use Providers\Sbo\Exceptions\TransactionAlreadyVoidException;
use Providers\Sbo\Exceptions\TransactionAlreadyExistException;
use Providers\Sbo\Exceptions\InvalidTransactionStatusException;
use Providers\Sbo\SportsbookDetails\SboSettleSportsbookDetails;
use Providers\Sbo\Exceptions\TransactionAlreadySettledException;
use Providers\Sbo\SportsbookDetails\SboMinigameSportsbookDetails;
use Providers\Sbo\Exceptions\ProviderTransactionNotFoundException;
use Providers\Sbo\SportsbookDetails\SboSettleParlaySportsbookDetails;
use Providers\Sbo\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;

class SboService
{
    const PROVIDER_TIMEZONE = 'GMT-4';
    const CASINO_MOBILE = 0;
    const SBO_MOBILE = 'm';
    const SBO_DESKTOP = 'd';
    const SBO_RNG_PRODUCTS = [3, 7];

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

    private function addBetRNG(
        object $transaction,
        float $balance,
        float $newTotalBetAmount,
        ICredentials $credentials,
        string $betTime
    ): float {
        if (trim($transaction->flag) == 'settled' || trim($transaction->flag) == 'void')
            throw new InvalidTransactionStatusException(data: $balance);

        if ($transaction->bet_amount > $newTotalBetAmount)
            throw new InvalidBetAmountException(data: $balance);

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $sportsbookDetails = (object)[
                'gameCode' => $transaction->game_code,
                'betChoice' => $transaction->bet_choice,
                'result' => $transaction->result,
                'event' => $transaction->event,
                'match' => $transaction->match,
                'market' => '-',
                'hdp' => $transaction->hdp,
                'odds' => $transaction->odds,
                'opt' => '-',
                'sportsType' => $transaction->sports_type
            ];

            if (trim($transaction->flag) == 'running') {
                $sportsbookReports = $this->walletReport->sportsbookBetReport(
                    trxID: $transaction->trx_id,
                    betTime: $betTime,
                    sportsbookDetails: $sportsbookDetails
                );

                $payoutResponse = $this->wallet->payout(
                    credentials: $credentials,
                    playID: $transaction->play_id,
                    currency: $transaction->currency,
                    transactionID: "payout-1-{$transaction->trx_id}",
                    amount: $transaction->payout_amount,
                    report: $sportsbookReports
                );

                if ($payoutResponse['status_code'] != 2100)
                    throw new WalletException;
            }

            $this->repository->inactiveTransaction($transaction->trx_id);

            $betID = "wager-2-{$transaction->trx_id}";

            $this->repository->createTransaction(
                betID: $betID,
                trxID: $transaction->trx_id,
                playID: $transaction->play_id,
                currency: $transaction->currency,
                betAmount: $newTotalBetAmount,
                betTime: $betTime,
                flag: 'running-inc',
                sportsbookDetails: $sportsbookDetails
            );

            $resettleResponse = $this->wallet->resettle(
                credentials: $credentials,
                playID: $transaction->play_id,
                currency: $transaction->currency,
                transactionID: $betID,
                amount: $transaction->bet_amount - $newTotalBetAmount,
                betID: $transaction->trx_id,
                settledTransactionID: $transaction->bet_id,
                betTime: $betTime,
            );

            if ($resettleResponse['status_code'] != 2100)
                throw new WalletException;

            DB::connection('pgsql_write')->commit();
        } catch (\Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $resettleResponse['credit_after'];
    }

    private function bet(
        string|int $gameID,
        string $trxID,
        string $playID,
        string $currency,
        float $betAmount,
        string $betTime,
        ICredentials $credentials
    ): float {
        try {
            $sportsbookDetails = (object)[
                'gameCode' => $gameID,
                'betChoice' => '-',
                'result' => '-',
                'event' => '-',
                'match' => '-',
                'market' => '-',
                'hdp' => '-',
                'odds' => '0',
                'opt' => '-',
                'sportsType' => match ($gameID) {
                    285 => 'Mini Mines',
                    286 => 'Mini Football Strike',
                    default => '-'
                },
            ];

            $betID = "wager-1-{$trxID}";

            $this->repository->createTransaction(
                betID: $betID,
                trxID: $trxID,
                playID: $playID,
                currency: $currency,
                betAmount: $betAmount,
                betTime: $betTime,
                flag: 'running',
                sportsbookDetails: $sportsbookDetails
            );

            $sportsbookReports = $this->walletReport->sportsbookBetReport(
                trxID: $trxID,
                betTime: $betTime,
                sportsbookDetails: $sportsbookDetails
            );

            $wagerResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $playID,
                currency: $currency,
                transactionID: $betID,
                amount: $betAmount,
                report: $sportsbookReports
            );

            if ($wagerResponse['status_code'] != 2100)
                throw new WalletException;

            DB::connection('pgsql_write')->commit();
        } catch (\Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $wagerResponse['credit_after'];
    }

    public function deduct(Request $request): float
    {
        $playID = str_replace('sbo_', '', $request->Username);

        $player = $this->repository->getPlayerByPlayID(playID: $playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency($player->currency);

        if ($request->CompanyKey != $credentials->getCompanyKey())
            throw new InvalidCompanyKeyException;

        $balance = $this->getWalletBalance(credentials: $credentials, playID: $playID);

        if ($balance < $request->Amount)
            throw new InsufficientFundException(data: $balance);

        $transaction = $this->repository->getTransactionByTrxID(trxID: $request->TransferCode);

        $betTime = Carbon::parse($request->BetTime, self::PROVIDER_TIMEZONE)
            ->setTimezone(8)
            ->format('Y-m-d H:i:s');

        if (is_null($transaction) === false) {
            if (in_array($request->ProductType, self::SBO_RNG_PRODUCTS) === true)
                return $this->addBetRNG(
                    transaction: $transaction,
                    balance: $balance,
                    newTotalBetAmount: $request->Amount,
                    credentials: $credentials,
                    betTime: $betTime
                );

            throw new TransactionAlreadyExistException(data: $balance);
        }

        return $this->bet(
            gameID: $request->GameId,
            trxID: $request->TransferCode,
            playID: $playID,
            currency: $player->currency,
            betAmount: $request->Amount,
            betTime: $betTime,
            credentials: $credentials
        );
    }

    private function isRollback(string $flag): bool
    {
        if (trim($flag) === 'rollback')
            return true;

        return false;
    }

    private function isMixParlay(object $betDetails): bool
    {
        return ($betDetails->sportsType ?? null) === 'Mix Parlay'
            || ($betDetails->productType ?? null) === 'MixParlayDesktop';
    }

    public function settle(Request $request): float
    {
        $playID = str_replace('sbo_', '', $request->Username);

        $playerData = $this->repository->getPlayerByPlayID(playID: $playID);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->CompanyKey !== $credentials->getCompanyKey())
            throw new InvalidCompanyKeyException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->TransferCode);
        $transactionDataFlag = $transactionData ? trim($transactionData->flag) : null;

        $transactionHasData = in_array($transactionDataFlag, ['settled', 'void'], true);

        if ($transactionHasData || $transactionDataFlag === null) {
            $balance = $this->getWalletBalance(credentials: $credentials, playID: $playID);

            match ($transactionDataFlag) {
                'settled' => throw new TransactionAlreadySettledException(data: $balance),
                'void' => throw new TransactionAlreadyVoidException(data: $balance),
                null => throw new ProviderTransactionNotFoundException(data: $balance),
            };
        }

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

            $isRollback = $this->isRollback(flag: $transactionDataFlag);

            if ($isRollback === true) {
                $resettleCount = $this->repository->getRollbackCount(trxID: $request->TransferCode);
                $betID = "resettle-{$resettleCount}-{$request->TransferCode}";
            } else {
                $settleCount = $this->repository->getSettleCount(trxID: $request->TransferCode) + 1;
                $betID = "payout-{$settleCount}-{$request->TransferCode}";
            }

            $this->repository->createSettleTransaction(
                trxID: $request->TransferCode,
                betID: $betID,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                betAmount: $transactionData->bet_amount,
                payoutAmount: $request->WinLoss,
                settleTime: $isRollback === true ? $transactionData->bet_time : $transactionDate,
                sportsbookDetails: $sportsbookDetails
            );

            if ($isRollback === true) {
                $walletResponse = $this->wallet->resettle(
                    credentials: $credentials,
                    playID: $playerData->play_id,
                    currency: $playerData->currency,
                    transactionID: $betID,
                    amount: $request->WinLoss - $transactionData->payout_amount,
                    betID: $request->TransferCode,
                    settledTransactionID: $transactionData->bet_id,
                    betTime: $transactionDate
                );
            } else {
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
