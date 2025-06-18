<?php

namespace Providers\Ors\DTO;

use Illuminate\Http\Request;

class OrsRequestDTO
{
    public function __construct(
        public readonly ?string $key = null,
        public readonly ?string $playID = null,
        public readonly ?string $signature = null,
        public readonly ?int $gameID = null,
        public readonly ?float $totalAmount = null,
        public readonly ?string $content = null,
        public readonly ?array $records = [],
        public readonly ?int $dateTime = null,
        public readonly ?array $all = [],
    ) {}

    public static function fromDebitRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            signature: $request->signature,
            gameID: $request->game_id,
            totalAmount: $request->total_amount,
            content: $request->getContent(),
            records: $request->records,
            dateTime: $request->called_at,
            all: $request->all()
        );
    }
}
