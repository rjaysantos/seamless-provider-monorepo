<?php

namespace Providers\Red;

use Exception;
use Providers\Red\RedApi;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Red\RedRepository;
use Providers\Red\RedCredentials;
use Providers\Red\DTO\RedPlayerDTO;
use Providers\Red\DTO\RedRequestDTO;
use Providers\Red\DTO\RedTransactionDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Red\Contracts\ICredentials;
use App\Exceptions\Casino\WalletErrorException;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
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

    public function wager(RedRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByUserIDProvider(providerUserID: $requestDTO->providerUserID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $wagerTransactionDTO = RedTransactionDTO::wager(
            extID: "wager-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            playerDTO: $player
        );

        $existingWagerTransaction = $this->repository->getTransactionByExtID(extID: $wagerTransactionDTO->extID);

        if (is_null($existingWagerTransaction) === false)
            throw new TransactionAlreadyExistsException;

        $balance = $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        if ($balance < $wagerTransactionDTO->betAmount)
            throw new InsufficientFundException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $wagerTransactionDTO);

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

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function payout(RedRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByUserIDProvider(providerUserID: $requestDTO->providerUserID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $wagerTransaction = $this->repository->getTransactionByExtID(extID: "wager-{$requestDTO->roundID}");

        if (is_null($wagerTransaction) === true)
            throw new TransactionDoesNotExistException;

        $payoutTransactionDTO = RedTransactionDTO::payout(
            extID: "payout-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            wagerTransactionDTO: $wagerTransaction
        );

        $existingPayoutTransaction = $this->repository->getTransactionByExtID(extID: $payoutTransactionDTO->extID);

        if (is_null($existingPayoutTransaction) === false)
            throw new TransactionAlreadySettledException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $payoutTransactionDTO);

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
                amount: $payoutTransactionDTO->winAmount,
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

    public function bonus(RedRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByUserIDProvider(providerUserID: $requestDTO->providerUserID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey != $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $bonusTransactionDTO = RedTransactionDTO::bonus(
            extID: "bonus-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            playerDTO: $player
        );

        $existingBonusTransaction = $this->repository->getTransactionByExtID(extID: $bonusTransactionDTO->extID);

        if (is_null($existingBonusTransaction) === false)
            throw new BonusTransactionAlreadyExists;

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
