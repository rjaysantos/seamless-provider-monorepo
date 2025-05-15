<?php

namespace App\Libraries\Wallet\V2;

use App\Contracts\V2\IWallet;
use App\Contracts\V2\IWalletCredentials;
use Wallet\V1\ProvSys\Transfer\Report;

class TestWallet implements IWallet
{
    public function Balance(
        IWalletCredentials $credentials,
        string $playID
    ): array {
        return [
            'play_id' => 'test-play-id',
            'credit' => 1000,
            'currency' => 'IDR',
            'last_update' => 1231321,
            'is_locked' => false,
            'is_disabled' => false,
            'status_code' => 2100
        ];
    }

    public function Wager(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        Report $report
    ): array {

        return [
            'play_id' => 'test-play-id',
            'currency' => 'IDR',
            'last_update' => 1231321,
            'transaction_id' => '12345',
            'credit_before' => 1000,
            'credit_after' => 1000,
            'status_code' => 2100
        ];
    }

    public function Payout(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        Report $report
    ): array {

        return [
            'play_id' => 'test-play-id',
            'currency' => 'IDR',
            'last_update' => 1231321,
            'transaction_id' => '12345',
            'credit_before' => 1000,
            'credit_after' => 1000,
            'status_code' => 2100
        ];
    }

    public function Cancel(
        IWalletCredentials $credentials,
        string $transactionID,
        float $amount,
        string $transactionIDToCancel
    ): array {

        return [
            'play_id' => 'test-play-id',
            'currency' => 'IDR',
            'last_update' => 1231321,
            'transaction_id' => '12345',
            'credit_before' => 1000,
            'credit_after' => 1000,
            'status_code' => 2100
        ];
    }

    public function Resettle(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        string $betID,
        string $settledTransactionID,
        string $betTime
    ): array {

        return [
            'play_id' => 'test-play-id',
            'currency' => 'IDR',
            'last_update' => 1231321,
            'transaction_id' => '12345',
            'credit_before' => 1000,
            'credit_after' => 1000,
            'status_code' => 2100
        ];
    }

    public function Bonus(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        Report $report
    ): array {

        return [
            'play_id' => 'test-play-id',
            'currency' => 'IDR',
            'last_update' => 1231321,
            'transaction_id' => '12345',
            'credit_before' => 1000,
            'credit_after' => 1000,
            'status_code' => 2100
        ];
    }

    public function WagerAndPayout(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $wagerTransactionID,
        float $wagerAmount,
        string $payoutTransactionID,
        float $payoutAmount,
        Report $report
    ): array {

        return [
            'play_id' => 'test-play-id',
            'currency' => 'IDR',
            'last_update' => 1231321,
            'transaction_id' => '12345',
            'credit_before' => 1000,
            'credit_after' => 1000,
            'status_code' => 2100
        ];
    }

    public function TransferIn(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        string $betTime
    ): array {

        return [
            'play_id' => 'test-play-id',
            'currency' => 'IDR',
            'transaction_id' => '12345',
            'credit_before' => 1000,
            'credit_after' => 1000,
            'status_code' => 2100
        ];
    }

    public function TransferOut(
        IWalletCredentials $credentials,
        string $playID,
        string $currency,
        string $transactionID,
        float $amount,
        string $betTime
    ): array {

        return [
            'play_id' => 'test-play-id',
            'currency' => 'IDR',
            'transaction_id' => '12345',
            'credit_before' => 1000,
            'credit_after' => 1000,
            'status_code' => 2100
        ];
    }
}
