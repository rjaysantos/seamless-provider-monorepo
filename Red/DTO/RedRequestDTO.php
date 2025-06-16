<?php

namespace Providers\Red\DTO;

use Carbon\Carbon;
use Illuminate\Http\Request;

class RedRequestDTO
{
    public function __construct(
        public readonly ?string $secretKey = null,
        public readonly ?int $providerUserID = null,
        public readonly ?string $gameID = null,
        public readonly ?string $roundID = null,
        public readonly ?float $amount = null,
        public readonly ?string $dateTime = null,
    ) {}

    public static function fromBalanceRequest(Request $request): self
    {
        return new self(
            secretKey: $request->header('secret-key'),
            providerUserID: $request->user_id
        );
    }

    public static function fromDebitRequest(Request $request): self
    {
        return new self(
            secretKey: $request->header('secret-key'),
            providerUserID: $request->user_id,
            gameID: $request->game_id,
            roundID: $request->txn_id,
            amount: $request->amount,
            dateTime: $request->debit_time
        );
    }

    public static function fromCreditRequest(Request $request): self
    {
        return new self(
            secretKey: $request->header('secret-key'),
            providerUserID: $request->user_id,
            gameID: $request->game_id,
            roundID: $request->txn_id,
            amount: $request->amount,
            dateTime: $request->credit_time
        );
    }

    public static function fromBonusRequest(Request $request): self
    {
        return new self(
            secretKey: $request->header('secret-key'),
            providerUserID: $request->user_id,
            amount: $request->amount,
            roundID: $request->txn_id,
            gameID: $request->game_id,
            dateTime: Carbon::now()->format('Y-m-d H:i:s')
        );
    }
}
