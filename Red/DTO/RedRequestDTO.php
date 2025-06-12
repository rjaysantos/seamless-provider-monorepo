<?php

namespace Providers\Red\DTO;

use Illuminate\Http\Request;

class RedRequestDTO
{
    public function __construct(
        public readonly ?string $secretKey = null,
        public readonly ?string $providerUserID = null,
        public readonly ?string $gameID = null,
        public readonly ?string $roundID = null,
        public readonly ?float $amount = null,
        public readonly ?string $dateTime = null,
    ) {}

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
}
