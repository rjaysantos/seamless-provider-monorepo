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
        public readonly ?string $gameID = null,
        public readonly ?float $betAmount = null,
        public readonly ?string $currency = null,
        public readonly ?string $roundID = null,
        public readonly ?string $dateTime = null,
        public readonly ?array $transactions = []
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

    public static function fromMultipleWithdrawRequest(Request $request): self
    {
        foreach ($request->datas as $data) {
            $transactions[] = new self(
                authToken: $request->header('Authorization'),
                playID: $data->playerId,
                agentID: $data->agentId,
                gameID: $data->gameCode,
                betAmount: $data->amount,
                currency: $data->currency,
                roundID: $data->gameRound,
                dateTime: $data->eventTime
            );
        }

        return new self(
            transactions: $transactions
        );
    }
}
