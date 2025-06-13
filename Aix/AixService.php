<?php

namespace Providers\Aix;

use Exception;
use Providers\Aix\AixApi;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Aix\AixRepository;
use Illuminate\Support\Facades\DB;
use Providers\Aix\DTO\AixRequestDTO;
use Providers\Aix\DTO\AixTransactionDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Aix\Contracts\ICredentials;
use App\Exceptions\Casino\WalletErrorException;
use Providers\Aix\DTO\AixPlayerDTO;
use Providers\Aix\Exceptions\PlayerNotFoundException;
use Providers\Aix\Exceptions\InsufficientFundException;
use Providers\Aix\Exceptions\InvalidSecretKeyException;
use Providers\Aix\Exceptions\TransactionAlreadyExistsException;
use Providers\Aix\Exceptions\TransactionAlreadySettledException;
use Providers\Aix\Exceptions\ProviderTransactionNotFoundException;
use Providers\Aix\Exceptions\WalletErrorException as ProviderWalletException;
use Providers\Aix\Exceptions\TransactionAlreadySettledException as DuplicateBonusException;


class AixService
{
    public function __construct(
        private AixRepository $repository,
        private AixCredentials $credentials,
        private IWallet $wallet,
        private AixApi $api,
        private WalletReport $walletReport
    ) {}

    public function getLaunchUrl(CasinoRequestDTO $casinoRequest): string
    {
        $player = AixPlayerDTO::fromPlayRequestDTO(casinoRequestDTO: $casinoRequest);

        $this->repository->createIgnorePlayer(playerDTO: $player);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $player->playID);

        if ($walletResponse['status_code'] != 2100)
            throw new WalletErrorException;

        return $this->api->auth(
            credentials: $credentials,
            player: $player,
            casinoRequest: $casinoRequest,
            balance: $walletResponse['credit']
        );
    }

    private function getWalletBalance(ICredentials $credentials, AixPlayerDTO $player): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $player->playID);

        if ($walletResponse['status_code'] != 2100)
            throw new ProviderWalletException;

        return $walletResponse['credit'];
    }

    public function balance(AixRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        return $this->getWalletBalance(credentials: $credentials, player: $player);
    }

    public function wager(AixRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $wagerTransactionDTO = AixTransactionDTO::wager(
            extID: "wager-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            playerDTO: $player
        );

        $existingTransactionData = $this->repository->getTransactionByExtID(extID: $wagerTransactionDTO->extID);

        if (is_null($existingTransactionData) === false)
            throw new TransactionAlreadyExistsException;

        $balance = $this->getWalletBalance(credentials: $credentials, player: $player);

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
                throw new ProviderWalletException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function payout(AixRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $wagerTransaction =  $this->repository->getTransactionByExtID(extID: "wager-{$requestDTO->roundID}");

        if (is_null($wagerTransaction) === true)
            throw new ProviderTransactionNotFoundException;

        $payoutTransactionDTO = AixTransactionDTO::payout(
            extID: "payout-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            wagerTransactionDTO: $wagerTransaction
        );

        $existingPayoutTransaction =  $this->repository->getTransactionByExtID(extID: $payoutTransactionDTO->extID);

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
                throw new ProviderWalletException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function bonus(AixRequestDTO $requestDTO)
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $bonusTransactionDTO = AixTransactionDTO::bonus(
            extID: "bonus-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            playerDTO: $player
        );

        $existingBonusTransaction = $this->repository->getTransactionByExtID(extID: $bonusTransactionDTO->extID);

        if (is_null($existingBonusTransaction) == false)
            throw new DuplicateBonusException;

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
                throw new ProviderWalletException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
