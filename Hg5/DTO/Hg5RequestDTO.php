<?php

namespace Providers\Hg5\DTO;

use Illuminate\Http\Request;

class Hg5RequestDTO
{
    public function __construct(
        public readonly ?string $bearerToken = null,
        public readonly ?string $playID = null,
        public readonly ?int $agentID = null,
        public readonly ?string $token = null,
        public readonly ?float $betAmount = null,
        public readonly ?float $winAmount = null,
        public readonly ?string $currency = null,
        public readonly ?string $gameID = null,
        public readonly ?string $roundID = null,
        public readonly ?string $dateTime = null,
        public readonly ?string $mainGameRound = null,
    ) {}

    public static function fromAuthenticateRequest(Request $request): self
    {
        return new self(
            bearerToken: $request->header('Authorization'),
            playID: $request->playerId,
            agentID: $request->agentId,
            token: $request->launchToken
        );
    }

    public static function fromWithdrawAndDepositRequest(Request $request): self
    {
        return new self(
            token: $request->header('Authorization'),
            playID: $request->playerId,
            currency: $request->currency,
            agentID: $request->agentId,
            betAmount: $request->withdrawAmount,
            winAmount: $request->depositAmount,
            gameID: $request->gameCode,
            roundID: $request->gameRound,
            dateTime: $request->eventTime,
            mainGameRound: $request->extra['slot']['mainGameRound'] ?? null
        );
    }
}
