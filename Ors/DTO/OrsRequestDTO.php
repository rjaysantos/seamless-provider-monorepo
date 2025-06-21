<?php

namespace Providers\Ors\DTO;

use Illuminate\Http\Request;

class OrsRequestDTO
{
    public function __construct(
        public readonly ?string $key = null,
        public readonly ?string $playID = null,
        public readonly ?string $extID = null,
        public readonly ?string $roundID = null,
        public readonly ?string $signature = null,
        public readonly ?string $currency = null,
        public readonly ?float  $amount = null,
        public readonly ?int    $gameID = null,
        public readonly ?int    $timestamp = null,
        public readonly ?object $rawRequest = null
    ) {}

    public static function fromCreditRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            amount: $request->winlose_amount,
            extID: $request->transaction_id,
            roundID: $request->round_id,
            gameID: $request->game_id,
            currency: $request->currency,
            timestamp: $request->called_at,
            signature: $request->signature,
            rawRequest: $request
        );
    }
}
