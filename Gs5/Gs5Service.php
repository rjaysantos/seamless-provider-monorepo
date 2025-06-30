<?php

namespace Providers\Gs5;

use Exception;
use Providers\Gs5\Gs5Api;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Gs5\Gs5Repository;
use Providers\Gs5\Gs5Credentials;
use Providers\Gs5\DTO\Gs5PlayerDTO;
use Providers\Gs5\DTO\GS5RequestDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Gs5\Contracts\ICredentials;
use App\Exceptions\Casino\PlayerNotFoundException;
use Providers\Gs5\Exceptions\WalletErrorException;
use Providers\Gs5\Exceptions\TokenNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Gs5\DTO\Gs5TransactionDTO;
use Providers\Gs5\Exceptions\InsufficientFundException;
use Providers\Gs5\Exceptions\ProviderWalletErrorException;
use Providers\Gs5\Exceptions\TransactionAlreadyExistsException;
use Providers\Gs5\Exceptions\TransactionAlreadySettledException;
use Providers\Gs5\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class Gs5Service
{
    private const PROVIDER_CURRENCY_CONVERSION = 100;

    public function __construct(
        private Gs5Repository $repository,
        private Gs5Credentials $credentials,
        private Gs5Api $api,
        private IWallet $wallet,
        private WalletReport $report,
    ) {}

    public function getLaunchUrl(CasinoRequestDTO $casinoRequest): string
    {
        $player = Gs5PlayerDTO::fromPlayRequestDTO(casinoRequestDTO: $casinoRequest);

        $this->repository->createOrUpdatePlayer(playerDTO: $player);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        return $this->api->getLaunchUrl(
            credentials: $credentials,
            playerDTO: $player,
            casinoRequestDTO: $casinoRequest
        );
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

        return $this->api->getGameHistory(credentials: $credentials, trxID: $transaction->roundID);
    }

    private function getPlayerBalance(ICredentials $credentials, string $playID): float
    {
        $balanceResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($balanceResponse['status_code'] !== 2100)
            throw new WalletErrorException;

        return $balanceResponse['credit'];
    }

    public function getBalance(GS5RequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByToken(token: $requestDTO->token);

        if (is_null($player) === true)
            throw new TokenNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $balance =  $this->getPlayerBalance(credentials: $credentials, playID: $player->playID);

        return $balance * self::PROVIDER_CURRENCY_CONVERSION;
    }

    public function authenticate(GS5RequestDTO $requestDTO): object
    {
        $player = $this->repository->getPlayerByToken(token: $requestDTO->token);

        if (is_null($player) === true)
            throw new TokenNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $balance =  $this->getPlayerBalance(credentials: $credentials, playID: $player->playID);

        return (object) [
            'player' => $player,
            'balance' => $balance * self::PROVIDER_CURRENCY_CONVERSION
        ];
    }

    public function wager(GS5RequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByToken(token: $requestDTO->token);

        if (is_null($player) === true)
            throw new TokenNotFoundException;

        $wagerTransactionDTO = Gs5TransactionDTO::wager(
            extID: "wager-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            playerDTO: $player,
            betAmount: $requestDTO->amount / self::PROVIDER_CURRENCY_CONVERSION
        );

        $existingTransaction = $this->repository->getTransactionByExtID(extID: $wagerTransactionDTO->extID);

        if (is_null($existingTransaction) === false)
            throw new TransactionAlreadyExistsException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $balance = $this->getPlayerBalance(credentials: $credentials, playID: $player->playID);

        if ($balance < $wagerTransactionDTO->betAmount)
            throw new InsufficientFundException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $wagerTransactionDTO);

            $report = $this->report->makeSlotReport(
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

        return $walletResponse['credit_after'] * self::PROVIDER_CURRENCY_CONVERSION;
    }

    public function payout(GS5RequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByToken(token: $requestDTO->token);

        if (is_null($player) === true)
            throw new TokenNotFoundException;

        $wagerTransaction = $this->repository->getTransactionByExtID(extID: "wager-{$requestDTO->roundID}");

        if (is_null($wagerTransaction) === true)
            throw new ProviderTransactionNotFoundException;

        $payoutTransactionDTO = Gs5TransactionDTO::payout(
            extID: "payout-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            wagerTransactionDTO: $wagerTransaction,
            winAmount: $requestDTO->amount / self::PROVIDER_CURRENCY_CONVERSION
        );

        $existingPayoutTransaction = $this->repository->getTransactionByExtID(extID: $payoutTransactionDTO->extID);

        if (is_null($existingPayoutTransaction) === false)
            throw new TransactionAlreadySettledException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $payoutTransactionDTO);

            $credentials = $this->credentials->getCredentialsByCurrency(currency: $payoutTransactionDTO->currency);

            $report = $this->report->makeSlotReport(
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
                throw new WalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'] * self::PROVIDER_CURRENCY_CONVERSION;
    }

    public function cancel(GS5RequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByToken(token: $requestDTO->token);

        if (is_null($player) === true)
            throw new TokenNotFoundException;

        $wagerTransaction = $this->repository->getTransactionByExtID(extID: "wager-{$requestDTO->roundID}");

        if (is_null($wagerTransaction) === true)
            throw new ProviderTransactionNotFoundException;

        $payoutTransaction = $this->repository->getTransactionByExtID(extID: "payout-{$requestDTO->roundID}");

        if (is_null($payoutTransaction) === false)
            throw new TransactionAlreadySettledException;

        try {
            $this->repository->beginTransaction();

            $cancelTransactionDTO = Gs5TransactionDTO::cancel(wagerTransactionDTO: $wagerTransaction);

            $this->repository->createTransaction(transactionDTO: $cancelTransactionDTO);

            $credentials = $this->credentials->getCredentialsByCurrency(currency: $cancelTransactionDTO->currency);

            $walletResponse = $this->wallet->cancel(
                credentials: $credentials,
                transactionID: $cancelTransactionDTO->extID,
                amount: $cancelTransactionDTO->betWinlose,
                transactionIDToCancel: $wagerTransaction->extID
            );

            if ($walletResponse['status_code'] != 2100)
                throw new WalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollBack();
            throw new $e;
        }

        return $walletResponse['credit_after'] * self::PROVIDER_CURRENCY_CONVERSION;
    }
}
