<?php

namespace Providers\Aix;

use Exception;
use Providers\Aix\AixApi;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use App\DTO\PlayerDTO;
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
        $playerDTO = AixPlayerDTO::fromCasinoRequestDTO(casinoRequestDTO: $casinoRequest);

        $this->repository->createIgnorePlayer(playerDTO: $playerDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDTO->currency);

        $walletResponse = $this->wallet->Balance(credentials: $credentials, playID: $playerDTO->playID);

        if ($walletResponse['status_code'] != 2100)
            throw new WalletErrorException;

        return $this->api->auth(
            credentials: $credentials,
            player: $playerDTO,
            casinoRequest: $casinoRequest,
            balance: $walletResponse['credit']
        );
    }

    private function getWalletBalance(ICredentials $credentials, PlayerDTO $player): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $player->playID);

        if ($walletResponse['status_code'] != 2100)
            throw new ProviderWalletException;

        return $walletResponse['credit'];
    }

    public function getBalance(AixRequestDTO $providerRequest): float
    {
        $player = $this->repository->getPlayerByPlayID(playID: $providerRequest->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($providerRequest->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        return $this->getWalletBalance(credentials: $credentials, player: $player);
    }

    public function bet(AixRequestDTO $aixRequest): float
    {
        $player = $this->repository->getPlayerByPlayID(playID: $aixRequest->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        if ($aixRequest->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $transactionData = $this->repository->getTransactionByExtID(extID: $aixRequest->debitExtID);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistsException;

        $balance = $this->getWalletBalance(credentials: $credentials, player: $player);

        $debitTransaction = AixTransactionDTO::fromBetRequest(aixRequest: $aixRequest, player: $player);

        if ($balance < $debitTransaction->betAmount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_report_write')->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $debitTransaction);

            $report = $this->walletReport->makeSlotReport(
                transactionID: $debitTransaction->trxID,
                gameCode: $debitTransaction->gameID,
                betTime: $debitTransaction->dateTime
            );

            $walletResponse = $this->wallet->wager(
                credentials: $credentials,
                playID: $debitTransaction->playID,
                currency: $debitTransaction->currency,
                transactionID: $debitTransaction->extID,
                amount: $debitTransaction->betAmount,
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

    public function settle(AixRequestDTO $aixRequest): float
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $aixRequest->playID);

        if (is_null($playerData) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($aixRequest->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $debitTransaction =  $this->repository->getTransactionByExtID(extID: $aixRequest->debitExtID);

        if (is_null($debitTransaction) === true)
            throw new ProviderTransactionNotFoundException;

        $transactionData = $this->repository->getTransactionByExtID(extID: $aixRequest->creditExtID);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadySettledException;

        $creditTransaction = AixTransactionDTO::fromCreditRequest(
            aixRequest: $aixRequest,
            transaction: $debitTransaction
        );

        try {
            DB::connection('pgsql_report_write')->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $creditTransaction);

            $report = $this->walletReport->makeSlotReport(
                transactionID: $creditTransaction->trxID,
                gameCode: $creditTransaction->gameID,
                betTime: $creditTransaction->dateTime
            );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $creditTransaction->playID,
                currency: $creditTransaction->currency,
                transactionID: $creditTransaction->extID,
                amount: $creditTransaction->betWinAmount,
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

    public function bonus(AixRequestDTO $aixRequest)
    {
        $playerData = $this->repository->getPlayerByPlayID(playID: $aixRequest->playID);

        if (is_null($playerData) === true)
            throw new PlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        if ($aixRequest->secretKey !== $credentials->getSecretKey())
            throw new InvalidSecretKeyException;

        $creditTransaction = $this->repository->getTransactionByExtID(extID: $aixRequest->creditExtID);

        if (is_null($creditTransaction) == true)
            throw new ProviderTransactionNotFoundException;

        $transactionData = $this->repository->getTransactionByExtID(extID: $aixRequest->bonusExtID);

        if (is_null($transactionData) == false)
            throw new DuplicateBonusException;

        $bonusTransaction = AixTransactionDTO::fromBonusRequest(
            aixRequest: $aixRequest,
            transaction: $creditTransaction
        );

        try {
            DB::connection('pgsql_report_write')->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $bonusTransaction);

            $report = $this->walletReport->makeBonusReport(
                transactionID: $bonusTransaction->trxID,
                gameCode: $bonusTransaction->gameID,
                betTime: $bonusTransaction->dateTime
            );

            $walletResponse = $this->wallet->bonus(
                credentials: $credentials,
                playID: $bonusTransaction->playID,
                currency: $bonusTransaction->currency,
                transactionID: $bonusTransaction->extID,
                amount: $bonusTransaction->betWinAmount,
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
