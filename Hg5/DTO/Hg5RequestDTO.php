<?php

namespace Providers\Hg5\DTO;

use Illuminate\Http\Request;

class Hg5RequestDTO
{
    public function __construct(
        public readonly ?string $authToken = null,
        public readonly ?string $playID = null,
        public readonly ?int $agentID = null,
        public readonly ?string $token = null,
    ) {}

    public static function fromAuthenticateRequest(Request $request): self
    {
        return new self(
            authToken: $request->header('Authorization'),
            playID: $request->playerId,
            agentID: $request->agentId,
            token: $request->launchToken
        );
    }

    public static function fromBalanceRequest(Request $request): self
    {
        return new self(
            authToken: $request->header('Authorization'),
            playID: $request->playerId,
            agentID: $request->agentId,
        );
    }
}
