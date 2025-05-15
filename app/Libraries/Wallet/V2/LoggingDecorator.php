<?php

namespace App\Libraries\Wallet\V2;

use App\Contracts\V2\IWallet;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Contracts\V2\IWalletCredentials;
use App\Libraries\Logger;

class LoggingDecorator implements IWallet
{
    private $logger;

    public function __construct(protected IWallet $wallet)
    {
        $this->logger = app()->make(Logger::class);
    }

    public function balance(
        IWalletCredentials $credentials,
        string $playID
    ): array {
        $response = $this->wallet->balance(credentials: $credentials, playID: $playID);

        $this->logger->logWallet(
            [
                'name' => 'balance',
                'playID' => $playID
            ],
            $response
        );

        return $response;
    }

    public function wager(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        Report $report
    ): array {
        $response = $this->wallet->wager(
            credentials: $credentials,
            playID: $playID,
            currency: $currency,
            transactionID: $transactionID,
            amount: $amount,
            report: $report
        );

        $this->logger->logWallet(
            [
                'name' => 'wager',
                'playID' => $playID,
                'currency' => $currency,
                'transactionID' => $transactionID,
                'amount' => $amount,
            ],
            $response
        );

        return $response;
    }

    public function payout(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        Report $report
    ): array {
        $response = $this->wallet->payout(
            credentials: $credentials,
            playID: $playID,
            currency: $currency,
            transactionID: $transactionID,
            amount: $amount,
            report: $report
        );

        $this->logger->logWallet(
            [
                'name' => 'payout',
                'playID' => $playID,
                'currency' => $currency,
                'transactionID' => $transactionID,
                'amount' => $amount,
            ],
            $response
        );

        return $response;
    }

    public function cancel(
        IWalletCredentials $credentials,
        string $transactionID,
        float $amount,
        string $transactionIDToCancel
    ): array {
        $response = $this->wallet->cancel(
            credentials: $credentials,
            transactionID: $transactionID,
            amount: $amount,
            transactionIDToCancel: $transactionIDToCancel
        );

        $this->logger->logWallet(
            [
                'name' => 'cancel',
                'transactionID' => $transactionID,
                'amount' => $amount,
                'transactionIDToCancel' => $transactionIDToCancel,
            ],
            $response
        );

        return $response;
    }

    public function resettle(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        string $betID,
        string $settledTransactionID,
        string $betTime
    ): array {

        $response = $this->wallet->resettle(
            credentials: $credentials,
            playID: $playID,
            currency: $currency,
            transactionID: $transactionID,
            amount: $amount,
            betID: $betID,
            settledTransactionID: $settledTransactionID,
            betTime: $betTime,
        );

        $this->logger->logWallet(
            [
                'name' => 'resettle',
                'playID' => $playID,
                'currency' => $currency,
                'transactionID' => $transactionID,
                'amount' => $amount,
                'betID' => $betID,
                'settledTransactionID' => $settledTransactionID,
                'betTime' => $betTime,
            ],
            $response
        );

        return $response;
    }

    public function bonus(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        Report $report
    ): array {
        $response = $this->wallet->bonus(
            credentials: $credentials,
            playID: $playID,
            currency: $currency,
            transactionID: $transactionID,
            amount: $amount,
            report: $report,
        );

        $this->logger->logWallet(
            [
                'name' => 'bonus',
                'playID' => $playID,
                'currency' => $currency,
                'transactionID' => $transactionID,
                'amount' => $amount,
            ],
            $response
        );

        return $response;
    }

    public function wagerAndPayout(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $wagerTransactionID,
        float $wagerAmount,
        string $payoutTransactionID,
        float $payoutAmount,
        Report $report
    ): array {

        $response = $this->wallet->wagerAndPayout(
            credentials: $credentials,
            playID: $playID,
            currency: $currency,
            wagerTransactionID: $wagerTransactionID,
            wagerAmount: $wagerAmount,
            payoutTransactionID: $payoutTransactionID,
            payoutAmount: $payoutAmount,
            report: $report,
        );

        $this->logger->logWallet(
            [
                'name' => 'wagerAndPayout',
                'playID' => $playID,
                'currency' => $currency,
                'wagerTransactionID' => $wagerTransactionID,
                'wagerAmount' => $wagerAmount,
                'payoutTransactionID' => $payoutTransactionID,
                'payoutAmount' => $payoutAmount,
            ],
            $response
        );

        return $response;
    }

    public function transferIn(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        string $betTime
    ): array {

        $response = $this->wallet->transferIn(
            credentials: $credentials,
            playID: $playID,
            currency: $currency,
            transactionID: $transactionID,
            amount: $amount,
            betTime: $betTime
        );

        $this->logger->logWallet(
            [
                'name' => 'transferIn',
                'play_id' => $playID,
                'currency' => $currency,
                'transaction_id' => $transactionID,
                'amount' => $amount,
                'betTime' => $betTime,
            ],
            $response
        );

        return $response;
    }

    public function transferOut(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        string $betTime
    ): array {

        $response = $this->wallet->transferOut(
            credentials: $credentials,
            playID: $playID,
            currency: $currency,
            transactionID: $transactionID,
            amount: $amount,
            betTime: $betTime
        );

        $this->logger->logWallet(
            [
                'name' => 'transferOut',
                'play_id' => $playID,
                'currency' => $currency,
                'transaction_id' => $transactionID,
                'amount' => $amount,
                'betTime' => $betTime,
            ],
            $response
        );

        return $response;
    }
}
