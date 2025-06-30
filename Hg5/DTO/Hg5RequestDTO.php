<?php

namespace Providers\Hg5\DTO;

use Illuminate\Http\Request;

class Hg5RequestDTO
{
    public function __construct(
        public readonly ?string $bearerToken = null,
        public readonly ?string $playID = null,
        public readonly ?int $agentID = null,
    ) {}

    public static function fromBalanceRequest(Request $request): self
    {
        return new self(
            bearerToken: $request->header('Authorization'),
            playID: $request->playerId,
            agentID: $request->agentId,
        );
    }
}
