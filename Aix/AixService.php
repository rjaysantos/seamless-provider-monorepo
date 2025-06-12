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

        $walletResponse = $this->wallet->Balance(credentials: $credentials, playID: $player->playID);

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

    public function getBalance(AixRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        return $this->getWalletBalance(credentials: $credentials, player: $player);
    }

    public function bet(AixRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $transactionDTO = AixTransactionDTO::bet(
            extID: "wager-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            playerDTO: $player
        );

        $existingTransactionData = $this->repository->getTransactionByExtID(extID: $transactionDTO->extID);

        if (is_null($existingTransactionData) === false)
            throw new TransactionAlreadyExistsException;

        $balance = $this->getWalletBalance(credentials: $credentials, player: $player);

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
                throw new ProviderWalletException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function settle(AixRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($requestDTO->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $betTransaction =  $this->repository->getTransactionByExtID(extID: "wager-{$requestDTO->roundID}");

        if (is_null($betTransaction) === true)
            throw new ProviderTransactionNotFoundException;

        $transactionDTO = AixTransactionDTO::settle(
            extID: "payout-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            betTransactionDTO: $betTransaction
        );

        $existingSettleTransaction =  $this->repository->getTransactionByExtID(extID: $transactionDTO->extID);

        if (is_null($existingSettleTransaction) === false)
            throw new TransactionAlreadySettledException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $transactionDTO);

            $report = $this->walletReport->makeSlotReport(
                transactionID: $transactionDTO->extID,
                gameCode: $transactionDTO->gameID,
                betTime: $transactionDTO->dateTime
            );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $transactionDTO->playID,
                currency: $transactionDTO->currency,
                transactionID: $transactionDTO->extID,
                amount: $transactionDTO->winAmount,
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
        $playerData = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($playerData) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($requestDTO->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $settleTransaction = $this->repository->getTransactionByExtID(extID: "payout-{$requestDTO->roundID}");

        if (is_null($settleTransaction) == true)
            throw new ProviderTransactionNotFoundException;

        $transactionDTO = AixTransactionDTO::bonus(
            extID: "bonus-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            settleTransactionDTO: $settleTransaction
        );

        $existingBonusTransaction = $this->repository->getTransactionByExtID(extID: $transactionDTO->extID);

        if (is_null($existingBonusTransaction) == false)
            throw new DuplicateBonusException;

        try {
            DB::connection('pgsql_report_write')->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $transactionDTO);

            $report = $this->walletReport->makeBonusReport(
                transactionID: $transactionDTO->roundID,
                gameCode: $transactionDTO->gameID,
                betTime: $transactionDTO->dateTime
            );

            $walletResponse = $this->wallet->bonus(
                credentials: $credentials,
                playID: $transactionDTO->playID,
                currency: $transactionDTO->currency,
                transactionID: $transactionDTO->extID,
                amount: $transactionDTO->winAmount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new ProviderWalletException;

            DB::connection('pgsql_report_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_report_write')->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
