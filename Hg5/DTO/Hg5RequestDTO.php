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
        public readonly ?string $currency = null,
        public readonly ?float $betAmount = null,
        public readonly ?string $gameID = null,
        public readonly ?string $roundID = null,
        public readonly ?string $dateTime = null,
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

    public static function fromRolloutRequest(Request $request): self
    {
        return new self(
            authToken: $request->header('Authorization'),
            playID: $request->playerId,
            agentID: $request->agentId,
            currency: $request->currency,
            betAmount: $request->amount,
            gameID: $request->gameCode,
            roundID: $request->gameRound,
            dateTime: $request->eventTime
        );
    }
}
