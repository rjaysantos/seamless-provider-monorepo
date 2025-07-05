<?php

namespace Providers\Ygr;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Ygr\Contracts\ICredentials;
use Providers\Ygr\Exceptions\WalletErrorException;
use Providers\Ygr\Exceptions\TokenNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Ygr\Exceptions\InsufficientFundException;
use Providers\Ygr\Exceptions\TransactionAlreadyExistsException;

class YgrService
{
    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public function __construct(
        private YgrRepository $repository,
        private YgrCredentials $credentials,
        private YgrApi $api,
        private Randomizer $randomizer,
        private IWallet $wallet,
        private WalletReport $walletReport
    ) {}

    private function getGameType(ICredentials $credentials, string $gameID): string
    {
        $apiResponse = $this->api->getGameList(credentials: $credentials);

        $gameList = collect($apiResponse);

        $gameData = $gameList->firstWhere('GameId', $gameID);

        if ($gameData->GameCategoryId == 1)
            return 'slot';

        return 'arcade';
    }

    public function getLaunchUrl(Request $request): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $request->playId);

        if (is_null($player) === true)
            $this->repository->createPlayer(
                playID: $request->playId,
                username: $request->username,
                currency: $request->currency
            );

        $credentials = $this->credentials->getCredentials();

        $gameType = $this->getGameType(credentials: $credentials, gameID: $request->gameId);

        $token = $this->randomizer->createToken();

        $this->repository->createOrUpdatePlayGame(
            playID: $request->playId,
            token: $token,
            gameID: "{$request->gameId}-{$gameType}"
        );

        return $this->api->launch(
            credentials: $credentials,
            token: $token,
            language: $request->language
        );
    }

    public function getBetDetail(Request $request): string
    {
        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->bet_id);

        if (is_null($transactionData) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentials();

        return $this->api->getBetDetailUrl(
            credentials: $credentials,
            transactionID: $request->bet_id,
            currency: $request->currency
        );
    }

    private function getPlayerBalance(ICredentials $credentials, string $playID): float
    {
        $balanceResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($balanceResponse['status_code'] != 2100)
            throw new WalletErrorException;

        return $balanceResponse['credit'];
    }

    public function getPlayerDetails(Request $request): object
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->connectToken);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $credentials = $this->credentials->getCredentials();

        $gameID = Str::beforeLast($playerData->status, '-');

        return (object) [
            'ownerId' => $credentials->getVendorID(),
            'parentId' => $credentials->getVendorID(),
            'gameId' => $gameID, // gameID inserted in status column of playgame table
            'userId' => $playerData->play_id,
            'nickname' => $playerData->username,
            'currency' => $playerData->currency,
            'balance' => $this->getPlayerBalance(credentials: $credentials, playID: $playerData->play_id)
        ];
    }

    public function deleteToken(Request $request): void
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->connectToken);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $this->repository->deletePlayGameByToken(token: $request->connectToken);
    }

    public function betAndSettle(Request $request): object
    {
        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->roundID);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistsException;

        $playerData = $this->repository->getPlayerByToken(token: $request->connectToken);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $credentials = $this->credentials->getCredentials();

        $balance = $this->getPlayerBalance(credentials: $credentials, playID: $playerData->play_id);

        if ($balance < $request->betAmount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->wagersTime, self::PROVIDER_API_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                transactionID: $request->roundID,
                betAmount: $request->betAmount,
                winAmount: $request->payoutAmount,
                transactionDate: $transactionDate
            );

            $gameID = Str::beforeLast($playerData->status, '-');
            $gameCategory = Str::afterLast($playerData->status, '-');

            if ($gameCategory == 'arcade')
                $report = $this->walletReport->makeArcadeReport(
                    transactionID: $request->roundID,
                    gameCode: $gameID, // gameID inserted from play api
                    betTime: $transactionDate
                );
            else
                $report = $this->walletReport->makeSlotReport(
                    transactionID: $request->roundID,
                    gameCode: $gameID, // gameID inserted from play api
                    betTime: $transactionDate
                );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                wagerTransactionID: $request->roundID,
                wagerAmount: $request->betAmount,
                payoutTransactionID: $request->roundID,
                payoutAmount: $request->payoutAmount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return (object) [
            'balance' => $walletResponse['credit_after'],
            'currency' => $playerData->currency
        ];
    }
}
