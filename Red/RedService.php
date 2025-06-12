<?php

namespace Providers\Red;

use Exception;
use Carbon\Carbon;
use Providers\Red\RedApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Red\RedRepository;
use Providers\Red\RedCredentials;
use Providers\Red\DTO\RedPlayerDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Red\Contracts\ICredentials;
use App\Exceptions\Casino\WalletErrorException;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Red\DTO\RedRequestDTO;
use Providers\Red\DTO\RedTransactionDTO;
use Providers\Red\Exceptions\InsufficientFundException;
use Providers\Red\Exceptions\InvalidSecretKeyException;
use Providers\Red\Exceptions\BonusTransactionAlreadyExists;
use Providers\Red\Exceptions\TransactionDoesNotExistException;
use Providers\Red\Exceptions\TransactionAlreadyExistsException;
use Providers\Red\Exceptions\TransactionAlreadySettledException;
use Providers\Red\Exceptions\WalletErrorException as ProviderWalletErrorException;
use Providers\Red\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;

class RedService
{
    private const PROVIDER_API_TIMEZONE = 'GMT+0';

    public function __construct(
        private RedRepository $repository,
        private RedCredentials $credentials,
        private RedApi $api,
        private IWallet $wallet,
        private WalletReport $walletReport
    ) {}

    public function getLaunchUrl(CasinoRequestDTO $casinoRequest): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $casinoRequest->playID) ??
            RedPlayerDTO::fromPlayRequestDTO(casinoRequestDTO: $casinoRequest);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $balanceResponse = $this->wallet->balance(
            credentials: $credentials,
            playID: $player->playID
        );

        if ($balanceResponse['status_code'] !== 2100)
            throw new WalletErrorException;

        $apiResponse = $this->api->authenticate(
            credentials: $credentials,
            requestDTO: $casinoRequest,
            playerDTO: $player,
            balance: $balanceResponse['credit']
        );

        $this->repository->createIgnorePlayer(playerDTO: $player, providerUserID: $apiResponse->userID);

        return $apiResponse->launchUrl;
    }

    public function getBetDetailUrl(CasinoRequestDTO $casinoRequest): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $casinoRequest->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $transaction = $this->repository->getTransactionByExtID(extID: $casinoRequest->extID);

        if (is_null($transaction) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        return $this->api->getBetResult(
            credentials: $credentials,
            transactionDTO: $transaction
        );
    }

    private function getPlayerBalance(ICredentials $credentials, RedPlayerDTO $playerDTO): float
    {
        $balanceResponse = $this->wallet->balance(credentials: $credentials, playID: $playerDTO->playID);

        if ($balanceResponse['status_code'] != 2100)
            throw new ProviderWalletErrorException;

        return $balanceResponse['credit'];
    }

    private function getPlayerDataByUserIDProvider(string $userID): object
    {
        $player = $this->repository->getPlayerByUserIDProvider(userIDProvider: $userID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        return $player;
    }

    public function balance(RedRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByUserIDProvider(providerUserID: $requestDTO->providerUserID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        return $this->getPlayerBalance(
            credentials: $credentials,
            playerDTO: $player
        );
    }

    private function convertProviderDateTime(string $dateTime): string
    {
        return Carbon::parse($dateTime, self::PROVIDER_API_TIMEZONE)
            ->setTimezone('GMT+8')
            ->format('Y-m-d H:i:s');
    }

    public function wager(RedRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByUserIDProvider(providerUserID: $requestDTO->providerUserID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $transactionDTO = RedTransactionDTO::bet(
            extID: "wager-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            playerDTO: $player
        );

        $existingTransaction = $this->repository->getTransactionByExtID(extID: $transactionDTO->extID);

        if (is_null($existingTransaction) === false)
            throw new TransactionAlreadyExistsException;

        $balance = $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        if ($balance < $transactionDTO->betAmount)
            throw new InsufficientFundException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $transactionDTO);

            $report = $this->walletReport->makeSlotReport(
                transactionID: $transactionDTO->roundID,
                gameCode: $transactionDTO->gameID,
                betTime: $transactionDTO->dateTime
            );

            $walletResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $transactionDTO->playID,
                currency: $transactionDTO->currency,
                transactionID: $transactionDTO->extID,
                amount: $transactionDTO->betAmount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function settle(Request $request): float
    {
        $playerData = $this->getPlayerDataByUserIDProvider(userID: $request->user_id);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->header('secret-key') != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $betTransactionData = $this->repository->getTransactionByExtID(extID: "wager-{$request->txn_id}");

        if (is_null($betTransactionData) === true)
            throw new TransactionDoesNotExistException;

        $extID = "payout-{$request->txn_id}";

        $transactionData = $this->repository->getTransactionByExtID(extID: $extID);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadySettledException;

        try {
            $this->repository->beginTransaction();

            $transactionDate = $this->convertProviderDateTime(dateTime: $request->credit_time);

            $this->repository->createTransaction(
                extID: $extID,
                playID: $betTransactionData->playID,
                username: $betTransactionData->username,
                currency: $betTransactionData->currency,
                gameCode: $betTransactionData->gameID,
                betAmount: 0,
                betWinlose: $request->amount - $betTransactionData->betAmount,
                transactionDate: $transactionDate
            );

            $report = $this->walletReport->makeSlotReport(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: $transactionDate
            );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                transactionID: $extID,
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function bonus(Request $request): float
    {
        $playerData = $this->getPlayerDataByUserIDProvider(userID: $request->user_id);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($request->header('secret-key') != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $extID = "bonus-{$request->txn_id}";

        $transactionData = $this->repository->getTransactionByExtID(extID: $extID);

        if (is_null($transactionData) === false)
            throw new BonusTransactionAlreadyExists;

        try {
            $this->repository->beginTransaction();

            $transactionDate = Carbon::now()->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                extID: $extID,
                playID: $playerData->play_id,
                username: $playerData->username,
                currency: $playerData->currency,
                gameCode: $request->game_id,
                betAmount: 0,
                betWinlose: $request->amount,
                transactionDate: $transactionDate
            );

            $report = $this->walletReport->makeBonusReport(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: $transactionDate
            );

            $walletResponse = $this->wallet->bonus(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                transactionID: $extID,
                amount: $request->amount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
