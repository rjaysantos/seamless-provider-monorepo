<?php

namespace Providers\Ors\DTO;

use Illuminate\Http\Request;

class OrsRequestDTO
{
    public function __construct(
        public readonly ?string $key = null,
        public readonly ?string $playID = null,
        public readonly ?string $signature = null,
        public readonly ?float  $amount = null,
        public readonly ?int    $gameID = null,
        public readonly ?array  $records = [],
        public readonly ?int    $timestamp = null,
        public readonly ?object $rawRequest = null
    ) {}

    public static function fromCreditRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            signature: $request->signature,
            gameID: $request->game_id,
            amount: $request->total_amount,
            records: $request->records,
            timestamp: $request->called_at,
            rawRequest: $request
        );
    }
}
