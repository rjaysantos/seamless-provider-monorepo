<?php

namespace Providers\Sab;

use Exception;
use Carbon\Carbon;
use Providers\Sab\SabApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Sab\SabRepository;
use Providers\Sab\SabCredentials;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Sab\Contracts\ICredentials;
use Providers\Sab\Exceptions\WalletErrorException;
use Providers\Sab\Exceptions\InvalidKeyException;
use Providers\Sab\Contracts\ISabSportsbookDetails;
use App\Exceptions\Casino\ThirdPartyApiErrorException;
use Providers\Sab\Exceptions\InsufficientFundException;
use Providers\Sab\SportsbookDetails\SabSportsbookDetails;
use Providers\Sab\Exceptions\TransactionAlreadyExistException;
use Providers\Sab\Exceptions\InvalidTransactionStatusException;
use Providers\Sab\SportsbookDetails\SabRunningSportsbookDetails;
use Providers\Sab\SportsbookDetails\SabSettledSportsbookDetails;
use Providers\Sab\Exceptions\ProviderThirdPartyApiErrorException;
use Providers\Sab\SportsbookDetails\SabMixParlaySportsbookDetails;
use Providers\Sab\SportsbookDetails\SabNumberGameSportsbookDetails;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Sab\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use Providers\Sab\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class SabService
{
    private const PROVIDER_TIMEZONE = 'GMT-4';
    private const CASINO_MOBILE = 0;
    private const SAB_MOBILE = 2;
    private const SAB_DESKTOP = 1;

    public function __construct(
        private SabRepository $repository,
        private SabCredentials $credentials,
        private SabApi $api,
        private IWallet $wallet,
        private WalletReport $walletReport
    ) {}

    public function getLaunchUrl(Request $request): string
    {
        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $userName = "{$credentials->getOperatorID()}_{$request->playId}{$credentials->getSuffix()}";

        $playerDetails = $this->repository->getPlayerByPlayID(playID: $request->playId);

        if (is_null($playerDetails) === true) {
            try {
                DB::connection('pgsql_write')->beginTransaction();

                $this->repository->createPlayer(
                    playID: $request->playId,
                    currency: $request->currency,
                    username: $userName
                );

                $this->api->createMember(credentials: $credentials, username: $userName);

                DB::connection('pgsql_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_write')->rollback();
                throw $e;
            }
        }

        $gameUrl = $this->api->getSabaUrl(
            credentials: $credentials,
            username: $userName,
            device: $request->device === self::CASINO_MOBILE ? self::SAB_MOBILE : self::SAB_DESKTOP
        );

        return "{$gameUrl}&lang={$request->language}&OType=3";
    }

    public function getBetDetailUrl(Request $request): string
    {
        $playerDetails = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($playerDetails) === true)
            throw new CasinoPlayerNotFoundException;

        $transactionDetails = $this->repository->getTransactionByTrxID(trxID: $request->bet_id);

        if (is_null($transactionDetails) === true)
            throw new CasinoTransactionNotFoundException;

        return 'https://' . request()->getHost() . '/sab/in/visual/' . Crypt::encryptString(value: $request->bet_id);
    }

    private function getSportsbookDetails(object $betDetails, string $ipAddress): ISabSportsbookDetails
    {
        $parlayData = isset($betDetails->ParlayData) ? $betDetails->ParlayData : null;

        return match ($betDetails->sport_type) {
            161, 164 => new SabNumberGameSportsbookDetails(
                sabSportsbookDetails: $betDetails,
                ipAddress: $ipAddress
            ),
            default => $parlayData == null ?
                new SabSportsbookDetails(
                    sabSportsbookDetails: $betDetails,
                    ipAddress: $ipAddress
                ) :
                new SabMixParlaySportsbookDetails(
                    sabSportsbookDetails: $betDetails,
                    ipAddress: $ipAddress
                )
        };
    }

    public function getBetDetailData(string $encryptedTrxID): array
    {
        $decryptedTransactionID = Crypt::decryptString(payload: $encryptedTrxID);

        $transactionDetails = $this->repository->getTransactionByTrxID(trxID: $decryptedTransactionID);

        if (is_null($transactionDetails) === true)
            throw new CasinoTransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $transactionDetails->currency);

        $betDetails = $this->api->getBetDetail(credentials: $credentials, transactionID: $decryptedTransactionID);

        $sportsbookDetails = $this->getSportsbookDetails(
            betDetails: $betDetails,
            ipAddress: $transactionDetails->ip_address
        );

        return [
            'ticketID' => $sportsbookDetails->getTicketID(),
            'dateTimeSettle' => $sportsbookDetails->getDateTimeSettle(),
            'event' => $sportsbookDetails->getEvent(),
            'match' => $sportsbookDetails->getMatch(),
            'betType' => $sportsbookDetails->getMarket(),
            'betChoice' => $sportsbookDetails->getBetChoice(),
            'hdp' => $sportsbookDetails->getHdp(),
            'odds' => $sportsbookDetails->getOdds(),
            'oddsType' => $sportsbookDetails->getOddsType(),
            'betAmount' => $sportsbookDetails->getStake(),
            'score' => $sportsbookDetails->getScore(),
            'status' => $sportsbookDetails->getResult(),
            'mixParlayData' => $sportsbookDetails->getMixParlayBets(),
            'singleParlayData' => $sportsbookDetails->getSingleParlayBets()
        ];
    }

    private function balance(ICredentials $credentials, string $playID): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($walletResponse['status_code'] !== 2100)
            throw new WalletErrorException;

        return $walletResponse['credit'];
    }

    public function getBalance(Request $request): float
    {
        $playerDetails = $this->repository->getPlayerByUsername(username: $request->message['userId']);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        if ($request->key !== $credentials->getVendorID())
            throw new InvalidKeyException;

        return $this->balance(credentials: $credentials, playID: $playerDetails->play_id) / $credentials->getCurrencyConversion();
    }

    private function createBet(
        string $username,
        string $requestKey,
        string $trxID,
        float $betAmount,
        string $gameCode,
        string $operationID,
        string $betDateTime,
        string $ipAddress
    ): void {
        $playerDetails = $this->repository->getPlayerByUsername(username: $username);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        if ($requestKey !== $credentials->getVendorID())
            throw new InvalidKeyException;

        $transactionDetails = $this->repository->getTransactionByTrxID(trxID: $trxID);

        if (is_null($transactionDetails) === false)
            throw new TransactionAlreadyExistException;

        $waitingBetAmount = $this->repository->getWaitingBetAmountByPlayID(playID: $playerDetails->play_id);

        $totalBalance = $this->balance(credentials: $credentials, playID: $playerDetails->play_id) - $waitingBetAmount;

        $totalBetAmount = $betAmount * $credentials->getCurrencyConversion();

        if ($totalBalance < $totalBetAmount)
            throw new InsufficientFundException;

        $sportsbookDetails = new SabRunningSportsbookDetails(gameCode: $gameCode);

        $this->repository->createTransaction(
            betID: "{$operationID}-{$trxID}",
            playID: $playerDetails->play_id,
            currency: $playerDetails->currency,
            trxID: $trxID,
            betAmount: $totalBetAmount,
            payoutAmount: 0,
            betDate: $betDateTime,
            ip: $ipAddress,
            flag: 'waiting',
            sportsbookDetails: $sportsbookDetails
        );
    }

    private function getSabConvertedDateTime(string $dateTime): string
    {
        return Carbon::parse($dateTime, self::PROVIDER_TIMEZONE)
            ->setTimezone('GMT+8')
            ->format('Y-m-d H:i:s');
    }

    public function placeBet(Request $request): void
    {
        $this->createBet(
            username: $request->message['userId'],
            requestKey: $request->key,
            trxID: $request->message['refId'],
            betAmount: $request->message['actualAmount'],
            gameCode: $request->message['betType'],
            operationID: $request->message['operationId'],
            betDateTime: $this->getSabConvertedDateTime(dateTime: $request->message['betTime']),
            ipAddress: $request->message['IP']
        );
    }

    public function placeBetParlay(Request $request): void
    {
        $exception = null;

        foreach ($request->message['txns'] as $transaction) {
            try {
                $this->createBet(
                    username: $request->message['userId'],
                    requestKey: $request->key,
                    trxID: $transaction['refId'],
                    betAmount: $transaction['betAmount'],
                    gameCode: 'Mix Parlay',
                    operationID: $request->message['operationId'],
                    betDateTime: $this->getSabConvertedDateTime(dateTime: $request->message['betTime']),
                    ipAddress: $request->message['IP']
                );
            } catch (Exception $e) {
                DB::connection('pgsql_write')->rollback();
                $exception = $e;
            }
        }

        if ($exception instanceof Exception)
            throw $exception;
    }

    public function confirmBet(Request $request): float
    {
        $playerDetails = $this->repository->getPlayerByUsername(username: $request->message['userId']);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        if ($request->key !== $credentials->getVendorID())
            throw new InvalidKeyException;

        $totalBetAmount = 0;
        foreach ($request->message['txns'] as $transaction) {
            $totalBetAmount += $transaction['actualAmount'] * $credentials->getCurrencyConversion();
        }

        $balance = $this->balance(credentials: $credentials, playID: $playerDetails->play_id);
        if ($balance < $totalBetAmount)
            throw new InsufficientFundException;

        $exception = null;
        foreach ($request->message['txns'] as $transaction) {
            $transactionDetails = $this->repository->getTransactionByTrxID(trxID: $transaction['refId']);

            if (is_null($transactionDetails) === true)
                throw new ProviderTransactionNotFoundException;

            if (trim($transactionDetails->flag) !== 'waiting')
                throw new InvalidTransactionStatusException;

            $duplicateTransaction = $this->repository->getTransactionByTrxID(trxID: $transaction['txId']);

            if (is_null($duplicateTransaction) === false)
                continue;

            $sportsbookDetails = new SabRunningSportsbookDetails(gameCode: $transactionDetails->game_code);

            $betID = "{$request->message['operationId']}-{$transaction['txId']}";
            $betAmount = $transaction['actualAmount'] * $credentials->getCurrencyConversion();

            try {
                DB::connection('pgsql_write')->beginTransaction();

                $this->repository->createTransaction(
                    betID: $betID,
                    playID: $playerDetails->play_id,
                    currency: $playerDetails->currency,
                    trxID: $transaction['txId'],
                    betAmount: $betAmount,
                    payoutAmount: 0,
                    betDate: $this->getSabConvertedDateTime(dateTime: $request->message['updateTime']),
                    ip: $transactionDetails->ip_address,
                    flag: 'running',
                    sportsbookDetails: $sportsbookDetails
                );

                $report = $this->walletReport->makeSportsbookReport(
                    trxID: $transaction['txId'],
                    betTime: $this->getSabConvertedDateTime(dateTime: $request->message['updateTime']),
                    sportsbookDetails: $sportsbookDetails
                );

                $walletResponse = $this->wallet->wager(
                    credentials: $credentials,
                    playID: $playerDetails->play_id,
                    currency: $playerDetails->currency,
                    transactionID: "wager-{$betID}",
                    amount: $betAmount,
                    report: $report
                );

                if ($walletResponse['status_code'] !== 2100)
                    throw new WalletErrorException;

                $balance = $walletResponse['credit_after'];

                DB::connection('pgsql_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_write')->rollback();
                $exception = $e;
            }
        }

        if ($exception instanceof Exception)
            throw $exception;

        return $balance / $credentials->getCurrencyConversion();
    }

    public function cancelBet(Request $request): float
    {
        $playerDetails = $this->repository->getPlayerByUsername(username: $request->message['userId']);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        if ($request->key !== $credentials->getVendorID())
            throw new InvalidKeyException;

        $exception = null;
        foreach ($request->message['txns'] as $transaction) {
            try {
                DB::connection('pgsql_write')->beginTransaction();

                $transactionDetails = $this->repository->getTransactionByTrxID(trxID: $transaction['refId']);

                if (is_null($transactionDetails) === true)
                    throw new ProviderTransactionNotFoundException;

                $betID = "{$request->message['operationId']}-{$transaction['refId']}";
                if ($betID === $transactionDetails->bet_id)
                    continue;

                if (trim($transactionDetails->flag) !== 'waiting')
                    throw new InvalidTransactionStatusException;

                $sportsbookDetails = new SabRunningSportsbookDetails(gameCode: $transactionDetails->game_code);

                $this->repository->createTransaction(
                    betID: $betID,
                    playID: $playerDetails->play_id,
                    currency: $playerDetails->currency,
                    trxID: $transaction['refId'],
                    betAmount: $transactionDetails->bet_amount,
                    payoutAmount: 0,
                    betDate: $this->getSabConvertedDateTime(dateTime: $request->message['updateTime']),
                    ip: $transactionDetails->ip_address,
                    flag: 'cancelled',
                    sportsbookDetails: $sportsbookDetails
                );

                DB::connection('pgsql_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_write')->rollback();
                $exception = $e;
            }
        }
        if ($exception instanceof Exception)
            throw $exception;

        return $this->balance(credentials: $credentials, playID: $playerDetails->play_id) / $credentials->getCurrencyConversion();
    }

    public function adjustBalance(Request $request): void
    {
        $playerDetails = $this->repository->getPlayerByUsername(username: $request->message['userId']);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        if ($request->key !== $credentials->getVendorID())
            throw new InvalidKeyException;

        $transactionDetails = $this->repository->getTransactionByTrxID(trxID: $request->message['txId']);

        if (is_null($transactionDetails) === false)
            return;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $totalBonusAmount = ($request->message['balanceInfo']['creditAmount']
                - $request->message['balanceInfo']['debitAmount']) * $credentials->getCurrencyConversion();

            $sportsbookDetails = new SabRunningSportsbookDetails(gameCode: $request->message['betType']);
            $transactionDate = $this->getSabConvertedDateTime(dateTime: $request->message['time']);

            $this->repository->createTransaction(
                betID: "{$request->message['operationId']}-{$request->message['txId']}",
                playID: $playerDetails->play_id,
                currency: $playerDetails->currency,
                trxID: $request->message['txId'],
                betAmount: 0,
                payoutAmount: $totalBonusAmount,
                betDate: $transactionDate,
                ip: null,
                flag: 'bonus',
                sportsbookDetails: $sportsbookDetails
            );

            if ($totalBonusAmount > 0)
                $walletResponse = $this->wallet->TransferIn(
                    credentials: $credentials,
                    playID: $playerDetails->play_id,
                    currency: $playerDetails->currency,
                    transactionID: "bonus-{$request->message['txId']}",
                    amount: $totalBonusAmount,
                    betTime: $transactionDate
                );
            else
                $walletResponse = $this->wallet->TransferOut(
                    credentials: $credentials,
                    playID: $playerDetails->play_id,
                    currency: $playerDetails->currency,
                    transactionID: "bonus-{$request->message['txId']}",
                    amount: abs($totalBonusAmount),
                    betTime: $transactionDate
                );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }
    }

    public function getRunningTransactions(Request $request): object
    {
        $runningTransactions = $this->repository->getAllRunningTransactions(
            webID: $request->branchId,
            currency: $request->currency,
            start: $request->start,
            length: $request->length
        );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $allData = [];
        foreach ($runningTransactions->data as $transactionDetails) {
            try {
                $betDetails = $this->api->getBetDetail(credentials: $credentials, transactionID: $transactionDetails->trx_id);

                $sportsbookDetails = $this->getSportsbookDetails(
                    betDetails: $betDetails,
                    ipAddress: $transactionDetails->ip_address
                );

                $sportsbookDetailsData = [
                    'game_type' => $sportsbookDetails->getMarket(),
                    'league' => $sportsbookDetails->getEvent(),
                    'match' => $sportsbookDetails->getMatch(),
                    'bet_option' => $sportsbookDetails->getBetChoice(),
                    'hdp' => $sportsbookDetails->getHdp(),
                    'odds' => $sportsbookDetails->getOdds(),
                    'odds_type' => $sportsbookDetails->getOddsType(),
                    'sports_type' => $sportsbookDetails->getMarket(),
                    'bet_choice' => $sportsbookDetails->getBetChoice(),
                    'bet_type' => $sportsbookDetails->getMarket(),
                ];
            } catch (Exception $e) {
                $sportsbookDetailsData = [
                    'game_type' => '-',
                    'league' => '-',
                    'match' => '-',
                    'bet_option' => '-',
                    'hdp' => '-',
                    'odds' => '-',
                    'odds_type' => '-',
                    'sports_type' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                ];
            }

            $allData[] =  array_merge($sportsbookDetailsData, [
                'id' => $transactionDetails->trx_id,
                'bet_id' => $transactionDetails->trx_id,
                'branch_id' => $transactionDetails->web_id,
                'play_id' => $transactionDetails->play_id,
                'bet_time' => $transactionDetails->bet_time,
                'bet_ip' => $transactionDetails->ip_address,
                'amount' => $transactionDetails->bet_amount,
                'live_score' => '-',
                'is_live' => '-',
                'ft_score' => '-',
                'is_first_half' => '-',
                'detail_link' => url('/') . '/sab/in/visual/' . Crypt::encryptString($transactionDetails->trx_id),
                'ht_score' => '-',
            ]);
        }

        return (object)[
            'totalCount' => $runningTransactions->totalCount,
            'data' => $allData
        ];
    }

    private function getSettledTransactionID(string $flag, string $betID)
    {
        if ($flag === 'settled')
            return "payout-{$betID}";

        return "resettle-{$betID}";
    }

    public function unsettle(Request $request): void
    {
        $playerDetails = $this->repository->getPlayerByUsername(username: $request->message['txns'][0]['userId']);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        if ($request->key !== $credentials->getVendorID())
            throw new InvalidKeyException;

        $transactionDetails = $this->repository->getTransactionByTrxID(trxID: $request->message['txns'][0]['txId']);

        if (is_null($transactionDetails) === true)
            throw new ProviderTransactionNotFoundException;

        $betID = "{$request->message['operationId']}-{$request->message['txns'][0]['txId']}";
        if ($betID === $transactionDetails->bet_id)
            return;

        if (in_array(trim($transactionDetails->flag), ['settled', 'resettled']) === false)
            throw new InvalidTransactionStatusException;

        $sportsbookDetails = new SabSettledSportsbookDetails(settledTransactionDetails: $transactionDetails);

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $this->repository->createTransaction(
                betID: $betID,
                playID: $playerDetails->play_id,
                currency: $playerDetails->currency,
                trxID: $request->message['txns'][0]['txId'],
                betAmount: $transactionDetails->bet_amount,
                payoutAmount: 0,
                betDate: $this->getSabConvertedDateTime(dateTime: $request->message['txns'][0]['updateTime']),
                ip: $transactionDetails->ip_address,
                flag: 'unsettled',
                sportsbookDetails: $sportsbookDetails
            );

            $walletResponse = $this->wallet->resettle(
                credentials: $credentials,
                playID: $playerDetails->play_id,
                currency: $playerDetails->currency,
                transactionID: "resettle-{$betID}",
                amount: -$transactionDetails->payout_amount,
                betID: $request->message['txns'][0]['txId'],
                settledTransactionID: $this->getSettledTransactionID(
                    flag: $transactionDetails->flag,
                    betID: $transactionDetails->bet_id
                ),
                betTime: $this->getSabConvertedDateTime(dateTime: $request->message['txns'][0]['updateTime']),
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }
    }

    public function resettle(Request $request): void
    {
        $playerDetails = $this->repository->getPlayerByUsername(username: $request->message['txns'][0]['userId']);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        if ($request->key !== $credentials->getVendorID())
            throw new InvalidKeyException;

        $transactionDetails = $this->repository->getTransactionByTrxID(trxID: $request->message['txns'][0]['txId']);

        if (is_null($transactionDetails) === true)
            throw new ProviderTransactionNotFoundException;

        $betID = "{$request->message['operationId']}-{$request->message['txns'][0]['txId']}";
        if ($betID === $transactionDetails->bet_id)
            return;

        if (in_array(trim($transactionDetails->flag), ['settled', 'resettled']) === false)
            throw new InvalidTransactionStatusException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $resettleAmount = $request->message['txns'][0]['payout'] * $credentials->getCurrencyConversion();
            $resettledDate = $this->getSabConvertedDateTime(dateTime: $request->message['txns'][0]['updateTime']);

            $sportsbookDetails = new SabSettledSportsbookDetails(settledTransactionDetails: $transactionDetails);

            $this->repository->createTransaction(
                betID: $betID,
                playID: $playerDetails->play_id,
                currency: $playerDetails->currency,
                trxID: $request->message['txns'][0]['txId'],
                betAmount: $transactionDetails->bet_amount,
                payoutAmount: $resettleAmount,
                betDate: $resettledDate,
                ip: $transactionDetails->ip_address,
                flag: 'resettled',
                sportsbookDetails: $sportsbookDetails
            );

            $walletResponse = $this->wallet->resettle(
                credentials: $credentials,
                playID: $playerDetails->play_id,
                currency: $playerDetails->currency,
                transactionID: "resettle-{$betID}",
                amount: $resettleAmount - $transactionDetails->payout_amount,
                betID: $request->message['txns'][0]['txId'],
                settledTransactionID: $this->getSettledTransactionID(
                    flag: $transactionDetails->flag,
                    betID: $transactionDetails->bet_id
                ),
                betTime: $resettledDate
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }
    }

    public function settle(Request $request): void
    {
        $exception = null;
        foreach ($request->message['txns'] as $transaction) {
            try {
                $playerDetails = $this->repository->getPlayerByUsername(username: $transaction['userId']);

                if (is_null($playerDetails) === true)
                    throw new ProviderPlayerNotFoundException;

                $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

                if ($request->key !== $credentials->getVendorID())
                    throw new InvalidKeyException;

                $transactionDetails = $this->repository->getTransactionByTrxID(trxID: $transaction['txId']);

                if (is_null($transactionDetails) === true)
                    throw new ProviderTransactionNotFoundException;

                $betID = "{$request->message['operationId']}-{$transaction['txId']}";
                if ($betID === $transactionDetails->bet_id)
                    continue;

                if (in_array(trim($transactionDetails->flag), ['running', 'unsettled']) === false)
                    throw new InvalidTransactionStatusException;

                $betDetails = $this->api->getBetDetail(credentials: $credentials, transactionID: $transaction['txId']);

                $sportsbookDetails = $this->getSportsbookDetails(
                    betDetails: $betDetails,
                    ipAddress: $transactionDetails->ip_address
                );

                DB::connection('pgsql_write')->beginTransaction();

                $settledAmount = $transaction['payout'] * $credentials->getCurrencyConversion();
                $transactionDate = $this->getSabConvertedDateTime(dateTime: $transaction['updateTime']);

                $this->repository->createTransaction(
                    betID: $betID,
                    playID: $playerDetails->play_id,
                    currency: $playerDetails->currency,
                    trxID: $transaction['txId'],
                    betAmount: $transactionDetails->bet_amount,
                    payoutAmount: $settledAmount,
                    betDate: $transactionDate,
                    ip: $transactionDetails->ip_address,
                    flag: 'settled',
                    sportsbookDetails: $sportsbookDetails
                );

                if (trim($transactionDetails->flag) === 'unsettled') {
                    $walletResponse = $this->wallet->resettle(
                        credentials: $credentials,
                        playID: $playerDetails->play_id,
                        currency: $playerDetails->currency,
                        transactionID: "resettle-{$betID}",
                        amount: $settledAmount,
                        betID: $transaction['txId'],
                        settledTransactionID: "resettle-{$transactionDetails->bet_id}",
                        betTime: $transactionDate
                    );
                } else {
                    $report = $this->walletReport->makeSportsbookReport(
                        trxID: $transaction['txId'],
                        betTime: $transactionDate,
                        sportsbookDetails: $sportsbookDetails
                    );

                    $walletResponse = $this->wallet->payout(
                        credentials: $credentials,
                        playID: $playerDetails->play_id,
                        currency: $playerDetails->currency,
                        transactionID: "payout-{$betID}",
                        amount: $settledAmount,
                        report: $report
                    );
                }

                if ($walletResponse['status_code'] !== 2100)
                    throw new WalletErrorException;

                DB::connection('pgsql_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_write')->rollBack();
                $exception = $e;
            }
        }

        if ($exception instanceof ThirdPartyApiErrorException)
            throw new ProviderThirdPartyApiErrorException;

        if ($exception instanceof Exception)
            throw $exception;
    }
}
