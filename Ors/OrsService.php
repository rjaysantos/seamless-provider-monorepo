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
use Providers\Ors\DTO\OrsPlayerDTO;
use Providers\Ors\DTO\OrsRequestDTO;
use Providers\Ors\DTO\OrsTransactionDTO;
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
        private WalletReport $walletReport
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

    private function verifyPlayerAccess(OrsRequestDTO $requestDTO, ICredentials $credentials): void
    {
        if ($requestDTO->key !== $credentials->getPublicKey())
            throw new InvalidPublicKeyException;

        if ($this->encryption->isSignatureValid(request: $requestDTO->rawRequest, credentials: $credentials) === false)
            throw new InvalidSignatureException;
    }

    private function getPlayerDetails(OrsRequestDTO $requestDTO): object
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        return $player;
    }

    private function getPlayerBalance(ICredentials $credentials, OrsPlayerDTO $playerDTO): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $playerDTO->playID);

        if ($walletResponse['status_code'] != 2100)
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

    public function balance(OrsRequestDTO $requestDTO): object
    {
        $player = $this->getPlayerDetails(requestDTO: $requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->verifyPlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        $balance = $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        return (object) [
            'balance' => $balance,
            'player' => $player
        ];
    }

    public function wager(OrsRequestDTO $requestDTO): float
    {
        $player = $this->getPlayerDetails(requestDTO: $requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->verifyPlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        $balance = $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        if ($balance < $requestDTO->totalAmount)
            throw new InsufficientFundException;

        foreach ($requestDTO->records as $record) {
            $existingTransaction = $this->repository->getTransactionByExtID(extID: "wager-{$record['transaction_id']}");

            if (is_null($existingTransaction) === false)
                throw new TransactionAlreadyExistsException;
        }

        foreach ($requestDTO->records as $record) {
            $wagerTransactionDTO = OrsTransactionDTO::wager(
                extID: "wager-{$record['transaction_id']}",
                record: $record,
                requestDTO: $requestDTO,
                playerDTO: $player
            );

            try {
                $this->repository->beginTransaction();

                $this->repository->createTransaction(transactionDTO: $wagerTransactionDTO);

                if (in_array($requestDTO->gameID, $credentials->getArcadeGameList()) === true)
                    $report = $this->walletReport->makeArcadeReport(
                        transactionID: $wagerTransactionDTO->roundID,
                        gameCode: $wagerTransactionDTO->gameID,
                        betTime: $wagerTransactionDTO->dateTime
                    );
                else
                    $report = $this->walletReport->makeSlotReport(
                        transactionID: $wagerTransactionDTO->roundID,
                        gameCode: $wagerTransactionDTO->gameID,
                        betTime: $wagerTransactionDTO->dateTime
                    );

                $walletResponse = $this->wallet->wager(
                    credentials: $credentials,
                    playID: $wagerTransactionDTO->playID,
                    currency: $wagerTransactionDTO->currency,
                    transactionID: $wagerTransactionDTO->extID,
                    amount: $wagerTransactionDTO->betAmount,
                    report: $report
                );

                if ($walletResponse['status_code'] !== 2100)
                    throw new WalletErrorException;

                $this->repository->commit();
            } catch (Exception $e) {
                $this->repository->rollback();
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
            $betTransaction = $this->repository->getBetTransactionByTrxID(transactionID: $record['transaction_id']);

            if (is_null($betTransaction) === true)
                throw new ProviderTransactionNotFoundException;
        }

        foreach ($request->records as $record) {
            try {
                DB::connection('pgsql_write')->beginTransaction();

                $this->repository->cancelBetTransaction(
                    transactionID: $record['transaction_id'],
                    cancelTme: Carbon::parse($request->called_at, self::PROVIDER_API_TIMEZONE)
                        ->setTimezone('GMT+8')
                        ->format('Y-m-d H:i:s')
                );

                $walletResponse = $this->wallet->cancel(
                    credentials: $credentials,
                    transactionID: "cancelBet-{$record['transaction_id']}",
                    amount: $record['amount'],
                    transactionIDToCancel: "wager-{$record['transaction_id']}"
                );

                if ($walletResponse['status_code'] !== 2100)
                    throw new WalletErrorException;

                DB::connection('pgsql_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_write')->rollBack();
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

        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->transaction_id);

        if (is_null($transactionData) === true)
            throw new ProviderTransactionNotFoundException;

        if (is_null($transactionData->updated_at) === false)
            return $this->getBalanceFromWallet(credentials: $credentials, playID: $request->player_id);

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $settleTime = Carbon::createFromTimestamp($request->called_at, self::PROVIDER_API_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->settleBetTransaction(
                transactionID: $request->transaction_id,
                winAmount: $request->amount,
                settleTime: $settleTime
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

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function bonus(OrsRequestDTO $requestDTO): float
    {
        $player = $this->getPlayerDetails(requestDTO: $requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->verifyPlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        $bonusTransactionDTO = OrsTransactionDTO::bonus(
            extID: "bonus-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            playerDTO: $player
        );

        $existingBonusTransaction = $this->repository->getTransactionByExtID(extID: $bonusTransactionDTO->extID);

        if (is_null($existingBonusTransaction) === false)
            throw new TransactionAlreadyExistsException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $bonusTransactionDTO);

            $report = $this->walletReport->makeBonusReport(
                transactionID: $bonusTransactionDTO->roundID,
                gameCode: $bonusTransactionDTO->gameID,
                betTime: $bonusTransactionDTO->dateTime
            );

            $walletResponse = $this->wallet->bonus(
                credentials: $credentials,
                playID: $bonusTransactionDTO->playID,
                currency: $bonusTransactionDTO->currency,
                transactionID: $bonusTransactionDTO->extID,
                amount: $bonusTransactionDTO->winAmount,
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
