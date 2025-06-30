<?php

namespace Providers\Hg5;

use Exception;
use Carbon\Carbon;
use Providers\Hg5\Hg5Api;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Hg5\Hg5Repository;
use Providers\Hg5\Hg5Credentials;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Providers\Hg5\DTO\Hg5RequestDTO;
use Illuminate\Support\Facades\Crypt;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Hg5\Contracts\ICredentials;
use App\Exceptions\Casino\PlayerNotFoundException;
use Providers\Hg5\Exceptions\GameNotFoundException;
use Providers\Hg5\Exceptions\InvalidTokenException;
use Providers\Hg5\Exceptions\InvalidAgentIDException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Hg5\DTO\Hg5PlayerDTO;
use Providers\Hg5\Exceptions\InsufficientFundException;
use Providers\Hg5\Exceptions\TransactionAlreadyExistsException;
use Providers\Hg5\Exceptions\TransactionAlreadySettledException;
use Providers\Hg5\Exceptions\WalletErrorException as ProviderWalletErrorException;
use Providers\Hg5\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use Providers\Hg5\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class Hg5Service
{
    private const PROVIDER_API_TIMEZONE = 'GMT-4';

    public function __construct(
        private Hg5Repository $repository,
        private Hg5Credentials $credentials,
        private Hg5Api $api,
        private IWallet $wallet,
        private WalletReport $walletReport
    ) {}

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

        $response = $this->api->getGameLink(
            credentials: $credentials,
            playID: $request->playId,
            gameCode: $request->gameId
        );

        $this->repository->createOrUpdatePlayGame(
            playID: $request->playId,
            token: $response->token
        );

        return $response->url;
    }

    private function getFishGameData(ICredentials $credentials, string $playID, object $transactionData): Collection
    {
        $apiResponse = $this->api->getOrderQuery(
            credentials: $credentials,
            playID: $playID,
            startDate: $transactionData->created_at,
            endDate: $transactionData->updated_at
        );

        $transactionID = Str::after($transactionData->trx_id, 'hg5-');

        return $apiResponse->where('gameroundid', $transactionID);
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

        $fishGameData = $this->getFishGameData(
            credentials: $credentials,
            playID: $request->play_id,
            transactionData: $transactionData
        );

        if ($fishGameData->isEmpty() === false)
            $url = request()->fullUrl() . '/' .
                Crypt::encryptString(value: $request->play_id) . '/' .
                Crypt::encryptString(value: $request->bet_id);
        else
            $url = $this->api->getOrderDetailLink(
                credentials: $credentials,
                transactionID: $request->bet_id,
                playID: $request->play_id
            );

        return $url;
    }

    public function getBetDetailData(string $encryptedPlayID, string $encryptedTrxID): array
    {
        $playID = Crypt::decryptString($encryptedPlayID);
        $trxID = Crypt::decryptString($encryptedTrxID);

        $playerData = $this->repository->getPlayerByPlayID(playID: $playID);

        if (is_null($playerData) === true)
            throw new PlayerNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $trxID);

        if (is_null($transactionData) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $fishGameData = $this->getFishGameData(
            credentials: $credentials,
            playID: $playID,
            transactionData: $transactionData
        );

        foreach ($fishGameData as $data)
            $roundData[] = [
                'roundID' => $data->round,
                'bet' => $data->bet,
                'win' => $data->win
            ];

        return [
            'playID' => $playID,
            'currency' => $playerData->currency,
            'trxID' => $trxID,
            'roundData' => $roundData
        ];
    }

    public function getFishGameDetailUrl(Request $request): string
    {
        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        return $this->api->getOrderDetailLink(
            credentials: $credentials,
            transactionID: $request->trxID,
            playID: $request->playID
        );
    }

    private function validatePlayerAccess(Hg5RequestDTO $requestDTO, ICredentials $credentials): void
    {
        if ($requestDTO->bearerToken !== $credentials->getAuthorizationToken())
            throw new InvalidTokenException;

        if ($requestDTO->agentID !== $credentials->getAgentID())
            throw new InvalidAgentIDException;
    }

    private function getPlayerBalance(ICredentials $credentials, Hg5PlayerDTO $playerDTO): float
    {
        $balanceResponse = $this->wallet->balance(credentials: $credentials, playID: $playerDTO->playID);

        if ($balanceResponse['status_code'] !== 2100)
            throw new ProviderWalletErrorException;

        return $balanceResponse['credit'];
    }

    public function balance(Hg5RequestDTO $requestDTO): object
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->validatePlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        $balance = $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        return (object) [
            'balance' => $balance,
            'player' => $player
        ];
    }

    public function authenticate(Request $request): object
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->launchToken);

        if (is_null($playerData) === true)
            throw new InvalidTokenException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $this->validatePlayerAccess(
            credentials: $credentials,
            request: $request
        );

        $balance = $this->getPlayerBalance(
            credentials: $credentials,
            playID: $playerData->play_id
        );

        return (object) [
            'playID' => $playerData->play_id,
            'currency' => $playerData->currency,
            'sessionID' => Str::uuid()->toString(),
            'balance' => $balance,
        ];
    }

    private function getConvertedTime(string $time): string
    {
        return Carbon::parse($time, self::PROVIDER_API_TIMEZONE)
            ->setTimezone(8)
            ->format('Y-m-d H:i:s');
    }

    private function isSlotGame(ICredentials $credentials, string $gameCode): bool
    {
        $response = $this->api->getGameList(credentials: $credentials);

        $gameData = $response->firstWhere('gamecode', $gameCode);

        if (is_null($gameData) === true)
            throw new GameNotFoundException;

        return $gameData->gametype == 'slot';
    }

    private function shortenBetID(string $betID): string
    {
        return md5($betID);
    }

    public function betAndSettle(Request $request): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->playerId);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $this->validatePlayerAccess(
            request: $request,
            credentials: $credentials
        );

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->gameRound);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistsException;

        if (isset($request->extra['slot']['mainGameRound']) === true) {
            $transactionData = $this->repository->getTransactionByTrxID(
                trxID: $request->extra['slot']['mainGameRound']
            );

            if (is_null($transactionData) === true)
                throw new ProviderTransactionNotFoundException;
        }

        $balance = $this->getPlayerBalance(
            credentials: $credentials,
            playID: $playerData->play_id
        );

        if ($balance < $request->withdrawAmount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = $this->getConvertedTime(time: $request->eventTime);

            $this->repository->createWagerAndPayoutTransaction(
                trxID: $request->gameRound,
                betAmount: $request->withdrawAmount,
                winAmount: $request->depositAmount,
                transactionDate: $transactionDate
            );

            $betID = $this->shortenBetID(betID: $request->gameRound);

            if ($this->isSlotGame(credentials: $credentials, gameCode: $request->gameCode) === true)
                $report = $this->walletReport->makeSlotReport(
                    transactionID: $betID,
                    gameCode: $request->gameCode,
                    betTime: $transactionDate,
                    opt: json_encode(['txn_id' => $request->gameRound])
                );
            else
                $report = $this->walletReport->makeArcadeReport(
                    transactionID: $betID,
                    gameCode: $request->gameCode,
                    betTime: $transactionDate,
                    opt: json_encode(['txn_id' => $request->gameRound])
                );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $request->playerId,
                currency: $request->currency,
                wagerTransactionID: "wager-{$request->gameRound}",
                wagerAmount: $request->withdrawAmount,
                payoutTransactionID: "payout-{$request->gameRound}",
                payoutAmount: $request->depositAmount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    private function betTransaction(object $request, string $authorization): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->playerId);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        if ($authorization !== $credentials->getAuthorizationToken())
            throw new InvalidTokenException;

        if ($request->agentId !== $credentials->getAgentID())
            throw new InvalidAgentIDException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->gameRound);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistsException;

        $balance = $this->getPlayerBalance(
            credentials: $credentials,
            playID: $request->playerId
        );

        if ($balance < $request->amount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = $this->getConvertedTime(time: $request->eventTime);

            $this->repository->createBetTransaction(
                trxID: $request->gameRound,
                betAmount: $request->amount,
                betTime: $transactionDate,
            );

            $betID = $this->shortenBetID(betID: $request->gameRound);

            $report = $this->walletReport->makeArcadeReport(
                transactionID: $betID,
                gameCode: $request->gameCode,
                betTime: $transactionDate,
                opt: json_encode(['txn_id' => $request->gameRound])
            );

            $walletResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $request->playerId,
                currency: $request->currency,
                transactionID: "wager-{$request->gameRound}",
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function bet(Request $request): float
    {
        return $this->betTransaction(request: $request, authorization: $request->header('Authorization'));
    }

    private function settleTransaction(object $request, string $authorization): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->playerId);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        if ($authorization !== $credentials->getAuthorizationToken())
            throw new InvalidTokenException;

        if ($request->agentId !== $credentials->getAgentID())
            throw new InvalidAgentIDException;

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->gameRound);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException;

        if (is_null($transactionData->updated_at) === false)
            throw new TransactionAlreadySettledException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = $this->getConvertedTime(time: $request->eventTime);

            $this->repository->settleTransaction(
                trxID: $request->gameRound,
                winAmount: $request->amount,
                settleTime: $transactionDate
            );

            $betID = $this->shortenBetID(betID: $request->gameRound);

            $report = $this->walletReport->makeArcadeReport(
                transactionID: $betID,
                gameCode: $request->gameCode,
                betTime: $transactionDate,
                opt: json_encode(['txn_id' => $request->gameRound])
            );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $request->playerId,
                currency: $request->currency,
                transactionID: "payout-{$request->gameRound}",
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function settle(Request $request): float
    {
        return $this->settleTransaction(request: $request, authorization: $request->header('Authorization'));
    }

    public function multipleBet(Request $request): array
    {
        foreach ($request->datas as $requestData) {
            $requestData = (object) $requestData;

            try {
                $balance = $this->betTransaction(request: $requestData, authorization: $request->header('Authorization'));

                $data = [
                    'code' => '0',
                    'message' => '',
                    'balance' => $balance
                ];
            } catch (Exception $e) {
                if (method_exists($e, 'render') === false)
                    throw $e;

                $data = $e->render()->original['status'];
            }

            $result = array_merge($data, [
                'currency' => $requestData->currency,
                'playerId' => $requestData->playerId,
                'agentId' => $requestData->agentId,
                'gameRound' => $requestData->gameRound
            ]);

            $totalData[] = (object) $result;
        }

        return $totalData;
    }

    public function multipleSettle(Request $request): array
    {
        foreach ($request->datas as $requestData) {
            $requestData = (object) $requestData;

            try {
                $balance = $this->settleTransaction(request: $requestData, authorization: $request->header('Authorization'));

                $data = [
                    'code' => '0',
                    'message' => '',
                    'balance' => $balance
                ];
            } catch (Exception $e) {
                if (method_exists($e, 'render') === false)
                    throw $e;

                $data = $e->render()->original['status'];
            }

            $result = array_merge($data, [
                'currency' => $requestData->currency,
                'playerId' => $requestData->playerId,
                'agentId' => $requestData->agentId,
                'gameRound' => $requestData->gameRound
            ]);

            $totalData[] = (object) $result;
        }

        return $totalData;
    }

    public function multiplayerBet(Request $request): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->playerId);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $this->validatePlayerAccess(
            request: $request,
            credentials: $credentials
        );

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->gameRound);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistsException;

        $balance = $this->getPlayerBalance(
            credentials: $credentials,
            playID: $request->playerId
        );

        if ($balance < $request->amount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = $this->getConvertedTime(time: $request->eventTime);

            $this->repository->createBetTransaction(
                trxID: $request->gameRound,
                betAmount: $request->amount,
                betTime: $transactionDate
            );

            $betID = $this->shortenBetID(betID: $request->gameRound);

            $report = $this->walletReport->makeArcadeReport(
                transactionID: $betID,
                gameCode: $request->gameCode,
                betTime: $transactionDate,
                opt: json_encode(['txn_id' => $request->gameRound])
            );

            $walletResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $request->playerId,
                currency: $request->currency,
                transactionID: "wager-{$request->gameRound}",
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function multiplayerSettle(Request $request): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $request->playerId);

        if (is_null($playerData) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        $this->validatePlayerAccess(
            request: $request,
            credentials: $credentials
        );

        $transactionData = $this->repository->getTransactionByTrxID(trxID: $request->gameRound);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException;

        if (is_null($transactionData->updated_at) === false)
            throw new TransactionAlreadySettledException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = $this->getConvertedTime(time: $request->eventTime);

            $this->repository->settleTransaction(
                trxID: $request->gameRound,
                winAmount: $request->amount,
                settleTime: $transactionDate
            );

            $betID = $this->shortenBetID(betID: $request->gameRound);

            $report = $this->walletReport->makeArcadeReport(
                transactionID: $betID,
                gameCode: $request->gameCode,
                betTime: $transactionDate,
                opt: json_encode(['txn_id' => $request->gameRound])
            );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $request->playerId,
                currency: $request->currency,
                transactionID: "payout-{$request->gameRound}",
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
