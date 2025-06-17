<?php

namespace Providers\Ors\DTO;

use Illuminate\Http\Request;

class OrsRequestDTO
{
    public function __construct(
        public readonly ?string $key = null,
        public readonly ?string $playID = null,
        public readonly ?string $signature = null,
        public readonly ?string $content = null,
        public readonly ?int $timestamp = null
    ) {}

    public static function fromBalanceRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            signature: $request->signature,
            content: $request->getContent(),
            timestamp: $request->timestamp
        );
    }
}
