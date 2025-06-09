<?php

namespace Providers\Aix\DTO;

use Carbon\Carbon;
use Illuminate\Http\Request;

class AixRequestDTO
{
    public function __construct(
        public readonly ?string $secretKey = null,
        public readonly ?string $playID = null,
        public readonly ?string $gameID = null,
        public readonly ?string $trxID = null,
        public readonly ?string $debitExtID = null,
        public readonly ?string $creditExtID = null,
        public readonly ?string $bonusExtID = null,
        public readonly ?string $amount = null,
        public readonly ?string $dateTime = null,
    ) {}

    public static function fromBalance(Request $request)
    {
        return new self(
            secretKey: $request->header('secret-key'),
            playID: $request->user_id,
            gameID: $request->prd_id
        );
    }

    public static function fromDebit(Request $request)
    {
        return new self(
            secretKey: $request->header('secret-key'),
            playID: $request->user_id,
            gameID: $request->prd_id,
            trxID: $request->txn_id,
            debitExtID: "wager-{$request->txn_id}",
            amount: $request->amount,
            dateTime: $request->debit_time
        );
    }

    public static function fromCredit(Request $request)
    {
        return new self(
            secretKey: $request->header('secret-key'),
            playID: $request->user_id,
            gameID: $request->prd_id,
            trxID: $request->txn_id,
            debitExtID: "wager-{$request->txn_id}",
            creditExtID: "payout-{$request->txn_id}",
            amount: $request->amount,
            dateTime: $request->credit_time
        );
    }

    public static function fromBonus(Request $request)
    {
        return new self(
            secretKey: $request->header('secret-key'),
            playID: $request->user_id,
            gameID: $request->prd_id,
            trxID: $request->txn_id,
            creditExtID: "payout-{$request->txn_id}",
            bonusExtID: "bonus-{$request->txn_id}",
            amount: $request->amount,
            dateTime: Carbon::now()->format('Y-m-d H:i:s')
        );
    }
}
