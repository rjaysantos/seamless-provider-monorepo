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
        public readonly ?float $amount = null,
        public readonly ?string $currency = null,
        public readonly ?string $gameID = null,
        public readonly ?string $mtCode = null,
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

    public static function fromBalanceRequest(Request $request): self
    {
        return new self(
            authToken: $request->header('Authorization'),
            playID: $request->playerId,
            agentID: $request->agentId,
        );
    }

    public static function fromWithdrawRequest(Request $request): self
    {
        return new self(
            authToken: $request->header('Authorization'),
            playID: $request->playerId,
            agentID: $request->agentId,
            amount: $request->amount,
            currency: $request->currency,
            gameID: $request->gameCode,
            mtCode: $request->gameRound,
            dateTime: $request->eventTime,
        );
    }
}
