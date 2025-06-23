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

    public function bet(Request $request): float
    {
        $playerData = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $this->verifyPlayerAccess(request: $request, credentials: $credentials);

        $balance = $this->getBalanceFromWallet(credentials: $credentials, playID: $request->player_id);

        if ($request->total_amount > $balance)
            throw new InsufficientFundException;

        foreach ($request->records as $record) {
            $transactionData = $this->repository->getTransactionByTrxID(transactionID: $record['transaction_id']);

            if (is_null($transactionData) === false)
                throw new TransactionAlreadyExistsException;
        }

        foreach ($request->records as $record) {
            try {
                DB::connection('pgsql_write')->beginTransaction();

                $betTime = Carbon::parse($request->called_at, self::PROVIDER_API_TIMEZONE)
                    ->setTimezone('GMT+8')
                    ->format('Y-m-d H:i:s');

                $this->repository->createBetTransaction(
                    transactionID: $record['transaction_id'],
                    betAmount: $record['amount'],
                    betTime: $betTime
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

                DB::connection('pgsql_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_write')->rollBack();
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

    public function settle(OrsRequestDTO $requestDTO): float
    {
        $player = $this->getPlayerDetails($requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->verifyPlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        $wagerTransactionData = $this->repository->getTransactionByExtID(extID: "wager-{$requestDTO->roundID}");

        if (is_null($wagerTransactionData) === true)
            throw new ProviderTransactionNotFoundException;

        $payoutTransactionDTO = OrsTransactionDTO::payout(
            extID: "payout-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            transactionDTO: $wagerTransactionData
        );

        $payoutTransaction = $this->repository->getTransactionByExtID(extID: $payoutTransactionDTO->extID);

        if (is_null($payoutTransaction) === false)
            return $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $payoutTransactionDTO);

            if (in_array($requestDTO->gameID, $credentials->getArcadeGameList()) === true)
                $report = $this->walletReport->makeArcadeReport(
                    transactionID: $payoutTransactionDTO->roundID,
                    gameCode: $payoutTransactionDTO->gameID,
                    betTime: $payoutTransactionDTO->dateTime
                );
            else
                $report = $this->walletReport->makeSlotReport(
                    transactionID: $payoutTransactionDTO->roundID,
                    gameCode: $payoutTransactionDTO->gameID,
                    betTime: $payoutTransactionDTO->dateTime
                );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $payoutTransactionDTO->playID,
                currency: $payoutTransactionDTO->currency,
                transactionID: $payoutTransactionDTO->extID,
                amount: $payoutTransactionDTO->betWinlose,
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
