<?php

namespace App\Contracts\V2;

use App\Contracts\V2\IWalletCredentials;
use Wallet\V1\ProvSys\Transfer\Report;

interface IWallet
{
    public function balance(
        IWalletCredentials $credentials,
        string $playID
    ): array;

    public function wager(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        Report $report
    ): array;

    public function payout(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        Report $report
    ): array;

    public function cancel(
        IWalletCredentials $credentials,
        string $transactionID,
        float $amount,
        string $transactionIDToCancel
    ): array;

    public function resettle(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        string $betID,
        string $settledTransactionID,
        string $betTime
    ): array;

    public function bonus(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        Report $report
    ): array;

    public function wagerAndPayout(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $wagerTransactionID,
        float $wagerAmount,
        string $payoutTransactionID,
        float $payoutAmount,
        Report $report
    ): array;

    public function TransferIn(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        string $betTime
    ): array;

    public function TransferOut(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        string $betTime
    ): array;
}
