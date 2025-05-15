<?php

namespace Providers\Bes;

use Carbon\Carbon;
use Providers\Bes\BesApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Bes\BesRepository;
use Providers\Bes\BesCredentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Bes\Exceptions\WalletException;
use Providers\Bes\Exceptions\PlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Bes\Exceptions\InsufficientFundException;
use Providers\Bes\Exceptions\TransactionAlreadyExistsException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;

class BesService
{
    public function __construct(
        private BesRepository $repository,
        private BesCredentials $credentials,
        private BesApi $api,
        private IWallet $wallet,
        private WalletReport $walletReport
    ) {
    }

    private function convertLang(string $lang)
    {
        return match ($lang) {
            'cn' => 'zh',
            'vn' => 'vi',
            default => $lang
        };
    }

    public function getLaunchUrl(Request $request): string
    {
        $playerDetails = $this->repository->getPlayerByPlayID(playID: $request->playId);

        if (is_null($playerDetails) === true)
            $this->repository->createPlayer(
                playID: $request->playId,
                username: $request->username,
                currency: $request->currency
            );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $gameUrl = $this->api->getKey(playID: $request->playId, credentials: $credentials);

        $gameOptions = [
            'aid' => $credentials->getAgentID(),
            'gid' => $request->gameId,
            'lang' => $this->convertLang(lang: $request->language),
            'return_url' => $request->host,
        ];

        return $gameUrl . '&' . http_build_query(data: $gameOptions);
    }

    public function getBetDetailUrl(Request $request): string
    {
        $playerDetails = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($playerDetails) === true)
            throw new CasinoPlayerNotFoundException;

        $transactionDetails = $this->repository->getTransactionByTrxID(transactionID: $request->bet_id);

        if (is_null($transactionDetails) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        return $this->api->getDetailsUrl(
            credentials: $credentials,
            transactionID: explode('-', $request->bet_id)[1]
        );
    }

    public function updateGamePosition(): void
    {
        $credentials = $this->credentials->getCredentialsByCurrency(currency: 'IDR');

        $gamelist = $this->api->getGameList(credentials: $credentials);

        foreach (collect($gamelist)->sortBy('SortID')->toArray() as $gameDetails) {
            $gameCodes[] = $gameDetails->gid;
        }

        $this->api->updateGamePosition(credentials: $credentials, gameCodes: $gameCodes);
    }

    public function getBalance(Request $request): float
    {
        $playerDetails = $this->repository->getPlayerByPlayID(
            playID: $request->uid
        );

        if (is_null($playerDetails) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(
            currency: $request->currency
        );

        $walletResponse = $this->wallet->balance(
            credentials: $credentials,
            playID: $request->uid
        );

        if ($walletResponse['status_code'] != 2100)
            throw new WalletException;

        return $walletResponse['credit'];
    }

    public function settleBet(Request $request): object
    {
        $playerDetails = $this->repository->getPlayerByPlayID(
            playID: $request->uid
        );

        if (is_null($playerDetails) === true)
            throw new PlayerNotFoundException;

        $transactionDetails = $this->repository->getTransactionByTrxID(
            transactionID: "{$request->roundId}-{$request->transId}"
        );

        if (is_null($transactionDetails) === false)
            throw new TransactionAlreadyExistsException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        $balanceResponse = $this->wallet->balance(
            credentials: $credentials,
            playID: $request->uid
        );

        if ($balanceResponse['status_code'] !== 2100)
            throw new WalletException;

        if ($balanceResponse['credit'] < $request->bet)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $this->repository->createTransaction(
                transactionID: "{$request->roundId}-{$request->transId}",
                betAmount: $request->bet,
                winAmount: $request->win
            );

            $report = $this->walletReport->makeSlotReport(
                transactionID: "{$request->roundId}-{$request->transId}",
                gameCode: $request->gid,
                betTime: Carbon::createFromTimestampMs($request->ts, 'Asia/Manila')
                    ->format('Y-m-d H:i:s')
            );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $request->uid,
                currency: $playerDetails->currency,
                wagerTransactionID: "{$request->roundId}-{$request->transId}",
                wagerAmount: $request->bet,
                payoutTransactionID: "{$request->roundId}-{$request->transId}",
                payoutAmount: $request->win,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new WalletException;

            DB::connection('pgsql_write')->commit();
        } catch (\Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw $e;
        }

        return (object) [
            'balance' => $walletResponse['credit_after'],
            'currency' => $playerDetails->currency
        ];
    }
}