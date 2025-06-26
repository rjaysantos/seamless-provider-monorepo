<?php

namespace Providers\Ors\DTO;

use Illuminate\Http\Request;

class OrsRequestDTO
{
    public function __construct(
        public readonly ?string $key = null,
        public readonly ?string $playID = null,
        public readonly ?string $signature = null,
        public readonly ?Request $rawRequest = null,
        public readonly ?int $gameID = null,
        public readonly ?float $amount = null,
        public readonly ?string $roundID = null,
        public readonly ?int $dateTime = null
    ) {}

    public static function fromBalanceRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            signature: $request->signature,
            rawRequest: $request
        );
    }

    public static function fromRewardRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'), 
            playID: $request->player_id,
            signature: $request->signature,
            gameID: $request->game_code,
            amount: $request->amount,
            roundID: $request->transaction_id,
            dateTime: $request->called_at,
            rawRequest: $request
        );
    }

    public static function fromCreditRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            amount: $request->amount,
            roundID: $request->transaction_id,
            gameID: $request->game_id,
            dateTime: $request->called_at,
            signature: $request->signature,
            rawRequest: $request
        );
    }
}
