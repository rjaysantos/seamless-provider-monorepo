<?php

namespace Providers\Ors\DTO;

use Illuminate\Http\Request;

class OrsRequestDTO
{
    public function __construct(
        public readonly ?string $key = null,
        public readonly ?string $playID = null,
        public readonly ?string $signature = null,
        public readonly ?string $extID = null,
        public readonly ?float  $amount = null,
        public readonly ?int    $gameID = null,
        public readonly ?string $content = null,
        public readonly ?int    $timestamp = null,
        public readonly ?array  $all = [],
    ) {}

    public static function fromCreditRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            signature: $request->signature,
            gameID: $request->game_id,
            amount: $request->total_amount,
            content: $request->getContent(),
            timestamp: $request->called_at,
            all: $request->all()
        );
    }
}
