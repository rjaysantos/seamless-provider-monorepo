<?php

namespace App\Libraries\Wallet\V2;

use App\Contracts\V2\IWallet;
use App\Contracts\V2\IWalletCredentials;
use Wallet\V1\ProvSys\Transfer\Request;
use Wallet\V1\ProvSys\Syncp\SyncpClient;
use Wallet\V1\ProvSys\Syncp\SyncCredit\Req;
use Wallet\V1\ProvSys\Transfer\TransferClient;
use Wallet\V1\ProvSys\Transfer\Request\General;
use Wallet\V1\ProvSys\Transfer\WagerAndPayoutRequest;
use Wallet\V1\ProvSys\Transfer\WagerAndPayoutRequest\General as WagerAndPayoutGeneral;
use Wallet\V1\ProvSys\Reconciliation\ResettlementRequest;
use Wallet\V1\ProvSys\Reconciliation\ReconciliationClient;
use Wallet\V1\ProvSys\Reconciliation\CanceledWagerOnlyByReferenceRequest;
use Wallet\V1\ProvSys\Transfer\Report;
use Wallet\V1\ProvSys\TransferCase\RequestCase;
use Wallet\V1\ProvSys\TransferCase\TransferCaseClient;

class Wallet implements IWallet
{
    public function Balance(
        IWalletCredentials $credentials,
        string $playID
    ): array {
        $request = new Req();
        $request->setPlayId($playID);
        $request->setProviderCode(strtolower($credentials->getProviderCode()));

        $credit = new SyncpClient($credentials->getGrpcHost() . ':' . $credentials->getGrpcPort(), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $meta = [
            'Authorization' => ['Bearer ' . $credentials->getGrpcToken()],
            'Signature'     => [$credentials->getGrpcSignature()]
        ];

        $response = $credit->Credit($request, $meta)->wait()[0];

        if ($response->getError()) {
            return [
                'status_code' => $response->getCode(),
                'error_message' => $response->getError()->getValue()
            ];
        }

        return [
            'play_id' => $response->getData()->getPlayId(),
            'credit' => $response->getData()->getCredit(),
            'currency' => $response->getData()->getCurrency(),
            'last_update' => $response->getData()->getLastUpdate(),
            'is_locked' => $response->getData()->getIsLocked(),
            'is_disabled' => $response->getData()->getIsDisabled(),
            'status_code' => $response->getCode()
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
        $general = new General();
        $general->setPlayId($playID);
        $general->setCurrency($currency);
        $general->setTransactionId($transactionID);
        $general->setAmount($amount);

        $request = new Request();
        $request->setGeneral($general);
        $request->setReport($report);

        $transfer = new TransferClient($credentials->getGrpcHost() . ':' . $credentials->getGrpcPort(), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $meta = [
            'Authorization' => ['Bearer ' . $credentials->getGrpcToken()],
            'Signature'     => [$credentials->getGrpcSignature()]
        ];

        $response = $transfer->Wager($request, $meta)->wait()[0];

        if ($response->getError()) {
            return [
                'status_code' => $response->getCode(),
                'error_message' => $response->getError()->getValue()
            ];
        }

        return [
            'play_id'           => $response->getData()->getPlayId(),
            'currency'          => $response->getData()->getCurrency(),
            'transaction_id'    => $response->getData()->getTransactionId(),
            'credit_before'     => $response->getData()->getCreditBefore(),
            'credit_after'      => $response->getData()->getCreditAfter(),
            'last_update'       => $response->getData()->getLastUpdate(),
            'status_code'       => $response->getCode()
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
        $general = new General();
        $general->setPlayId($playID);
        $general->setCurrency($currency);
        $general->setTransactionId($transactionID);
        $general->setAmount($amount);

        $request = new Request();
        $request->setGeneral($general);
        $request->setReport($report);
        $request->setMwapcd(false);

        $transfer = new TransferClient($credentials->getGrpcHost() . ':' . $credentials->getGrpcPort(), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $meta = [
            'Authorization' => ['Bearer ' . $credentials->getGrpcToken()],
            'Signature'     => [$credentials->getGrpcSignature()]
        ];

        $response = $transfer->Payout($request, $meta)->wait()[0];

        if ($response->getError()) {
            return [
                'status_code' => $response->getCode(),
                'error_message' => $response->getError()->getValue()
            ];
        }

        return [
            'play_id'           => $response->getData()->getPlayId(),
            'currency'          => $response->getData()->getCurrency(),
            'transaction_id'    => $response->getData()->getTransactionId(),
            'credit_before'     => $response->getData()->getCreditBefore(),
            'credit_after'      => $response->getData()->getCreditAfter(),
            'last_update'       => $response->getData()->getLastUpdate(),
            'status_code'       => $response->getCode()
        ];
    }

    public function Cancel(
        IWalletCredentials $credentials,
        string $transactionID,
        float $amount,
        string $transactionIDToCancel
    ): array {
        $request = new CanceledWagerOnlyByReferenceRequest();
        $request->setTransactionId($transactionID);
        $request->setAmount($amount);
        $request->setCanceledTransactionId($transactionIDToCancel);

        $recon = new ReconciliationClient($credentials->getGrpcHost() . ':' . $credentials->getGrpcPort(), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $meta = [
            'Authorization' => ['Bearer ' . $credentials->getGrpcToken()],
            'Signature'     => [$credentials->getGrpcSignature()]
        ];

        $response = $recon->CanceledWagerOnlyByReference($request, $meta)->wait()[0];

        if ($response->getError()) {
            return [
                'status_code' => $response->getCode(),
                'error_message' => $response->getError()->getValue()
            ];
        }

        return [
            'play_id'           => $response->getData()->getPlayId(),
            'currency'          => $response->getData()->getCurrency(),
            'transaction_id'    => $response->getData()->getTransactionId(),
            'credit_before'     => $response->getData()->getCreditBefore(),
            'credit_after'      => $response->getData()->getCreditAfter(),
            'last_update'       => $response->getData()->getLastUpdate(),
            'status_code'       => $response->getCode()
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
        $request = new ResettlementRequest();
        $request->setPlayId($playID);
        $request->setCurrency($currency);
        $request->setTransactionId($transactionID);
        $request->setAmount($amount);
        $request->setBetId($betID);
        $request->setSettledTransactionId($settledTransactionID);
        $request->setBetTime($betTime);

        $recon = new ReconciliationClient($credentials->getGrpcHost() . ':' . $credentials->getGrpcPort(), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $meta = [
            'Authorization' => ['Bearer ' . $credentials->getGrpcToken()],
            'Signature'     => [$credentials->getGrpcSignature()]
        ];

        $response = $recon->Resettlement($request, $meta)->wait()[0];

        if ($response->getError()) {
            return [
                'status_code' => $response->getCode(),
                'error_message' => $response->getError()->getValue()
            ];
        }

        return [
            'play_id'           => $response->getData()->getPlayId(),
            'currency'          => $response->getData()->getCurrency(),
            'transaction_id'    => $response->getData()->getTransactionId(),
            'credit_before'     => $response->getData()->getCreditBefore(),
            'credit_after'      => $response->getData()->getCreditAfter(),
            'last_update'       => $response->getData()->getLastUpdate(),
            'status_code'       => $response->getCode()
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
        $general = new General();
        $general->setPlayId($playID);
        $general->setCurrency($currency);
        $general->setTransactionId($transactionID);
        $general->setAmount($amount);

        $request = new Request();
        $request->setGeneral($general);
        $request->setReport($report);
        $request->setMwapcd(false);

        $transfer = new TransferClient($credentials->getGrpcHost() . ':' . $credentials->getGrpcPort(), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $meta = [
            'Authorization' => ['Bearer ' . $credentials->getGrpcToken()],
            'Signature'     => [$credentials->getGrpcSignature()]
        ];

        $response = $transfer->Bonus($request, $meta)->wait()[0];

        if ($response->getError()) {
            return [
                'status_code' => $response->getCode(),
                'error_message' => $response->getError()->getValue()
            ];
        }

        return [
            'play_id'           => $response->getData()->getPlayId(),
            'currency'          => $response->getData()->getCurrency(),
            'transaction_id'    => $response->getData()->getTransactionId(),
            'credit_before'     => $response->getData()->getCreditBefore(),
            'credit_after'      => $response->getData()->getCreditAfter(),
            'last_update'       => $response->getData()->getLastUpdate(),
            'status_code'       => $response->getCode()
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
        $general = new WagerAndPayoutGeneral();
        $general->setPlayId($playID);
        $general->setCurrency($currency);
        $general->setWagerTrxId($wagerTransactionID);
        $general->setWagerAmount($wagerAmount);
        $general->setPayoutTrxId($payoutTransactionID);
        $general->setPayoutAmount($payoutAmount);

        $request = new WagerAndPayoutRequest();
        $request->setGeneral($general);
        $request->setReport($report);

        $transfer = new TransferClient($credentials->getGrpcHost() . ':' . $credentials->getGrpcPort(), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $meta = [
            'Authorization' => ['Bearer ' . $credentials->getGrpcToken()],
            'Signature'     => [$credentials->getGrpcSignature()]
        ];

        $response = $transfer->WagerAndPayout($request, $meta)->wait()[0];

        if ($response->getError()) {
            return [
                'status_code' => $response->getCode(),
                'error_message' => $response->getError()->getValue()
            ];
        }

        return [
            'play_id'           => $response->getData()->getPlayId(),
            'currency'          => $response->getData()->getCurrency(),
            'transaction_id'    => $response->getData()->getTransactionId(),
            'credit_before'     => $response->getData()->getCreditBefore(),
            'credit_after'      => $response->getData()->getCreditAfter(),
            'last_update'       => $response->getData()->getLastUpdate(),
            'status_code'       => $response->getCode(),
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
        $transfer = new TransferCaseClient($credentials->getGrpcHost() . ':' . $credentials->getGrpcPort(), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $meta = [
            'Authorization' => ['Bearer ' . $credentials->getGrpcToken()],
            'Signature'     => [$credentials->getGrpcSignature()]
        ];

        $request = new RequestCase;
        $request->setPlayId($playID);
        $request->setCurrency($currency);
        $request->setTransactionId($transactionID);
        $request->setAmount($amount);
        $request->setBetTime($betTime);
        $request->setProviderCode($credentials->getProviderCode());

        $response = $transfer->TransferIn($request, $meta)->wait()[0];

        if ($response->getError()) {
            return [
                'status_code' => $response->getCode(),
                'error_message' => $response->getError()->getValue()
            ];
        }

        return [
            'play_id' => $response->getData()->getPlayId(),
            'currency' => $response->getData()->getCurrency(),
            'transaction_id' => $response->getData()->getTransactionId(),
            'credit_before' => $response->getData()->getCreditBefore(),
            'credit_after' => $response->getData()->getCreditAfter(),
            'last_update' => $response->getData()->getLastUpdate(),
            'status_code' => $response->getCode(),
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
        $transfer = new TransferCaseClient($credentials->getGrpcHost() . ':' . $credentials->getGrpcPort(), [
            'credentials' => \Grpc\ChannelCredentials::createInsecure()
        ]);

        $meta = [
            'Authorization' => ['Bearer ' . $credentials->getGrpcToken()],
            'Signature'     => [$credentials->getGrpcSignature()]
        ];

        $request = new RequestCase;
        $request->setPlayId($playID);
        $request->setCurrency($currency);
        $request->setTransactionId($transactionID);
        $request->setAmount($amount);
        $request->setBetTime($betTime);
        $request->setProviderCode($credentials->getProviderCode());

        $response = $transfer->TransferOut($request, $meta)->wait()[0];

        if ($response->getError()) {
            return [
                'status_code' => $response->getCode(),
                'error_message' => $response->getError()->getValue()
            ];
        }

        return [
            'play_id' => $response->getData()->getPlayId(),
            'currency' => $response->getData()->getCurrency(),
            'transaction_id' => $response->getData()->getTransactionId(),
            'credit_before' => $response->getData()->getCreditBefore(),
            'credit_after' => $response->getData()->getCreditAfter(),
            'last_update' => $response->getData()->getLastUpdate(),
            'status_code' => $response->getCode(),
        ];
    }
}
