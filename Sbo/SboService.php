<?php

namespace Providers\Sbo;

use Carbon\Carbon;
use Providers\Sbo\SboApi;
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
use Providers\Sbo\Exceptions\TransactionAlreadyExistException;
use Providers\Sbo\Exceptions\InvalidTransactionStatusException;
use Providers\Sbo\SportsbookDetails\SboRunningSportsbookDetails;
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

    public function deduct(Request $request): float
    {
        $playID = str_replace('sbo_', '', $request->Username);

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

        $betTime = Carbon::parse($request->BetTime, self::PROVIDER_TIMEZONE)
            ->setTimezone(8)
            ->format('Y-m-d H:i:s');

        try {
            DB::connection('pgsql_write')->beginTransaction();
            
            $gameCode = $request->GameId;

            $sportsbookDetails = (object)[
                'gameCode' => $gameCode,
                'betChoice' => '-',
                'result' => '-',
                'event' => '-',
                'match' => '-',
                'market' => '-',
                'hdp' => '-',
                'odds' => '0',
                'opt' => '-',
                'sportsType' => match ($gameCode) {
                    285 => 'Mini Mines',
                    286 => 'Mini Football Strike',
                    default => '-'
                },
            ];

            $betID = "wager-1-{$request->TransferCode}";

            $this->repository->createTransaction(
                betID: $betID,
                trxID: $request->TransferCode,
                playID: $playID,
                currency: $playerDetails->currency,
                betAmount: $request->Amount,
                betTime: $betTime,
                flag: 'running',
                sportsbookDetails: $sportsbookDetails
            );

            $sportsbookReports = $this->walletReport->makeSportsbookReport(
                trxID: $request->TransferCode,
                betTime: $betTime,
                sportsbookDetails: new SboRunningSportsbookDetails(gameCode: $gameCode)
            );

            $wagerResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $playID,
                currency: $playerDetails->currency,
                transactionID: $betID,
                amount: $request->Amount,
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
}
