<?php

namespace Providers\Pla\DTO;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PlaRequestDTO
{
    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $requestId = null,
        public readonly ?string $username = null,
        public readonly ?string $token = null,
        public readonly ?string $extID = null,
        public readonly ?string $gameID = null,
        public readonly ?string $roundID = null,
        public readonly ?string $dateTime = null,
        public readonly ?string $transactionType = null,
        public readonly ?float $amount = null,
        public readonly ?float $winAmount = null,
        public readonly ?string $betExtID = null,
    ){}

    public static function fromAuthenticateRequest(Request $request): self
    {
        return new self(
            requestId: $request->requestId,
            playID: strtolower(Str::after($request->username, '_')),
            username: $request->username,
            token: $request->externalToken
        );
    }

    public static function fromGetBalanceRequest(Request $request): self
    {
        return new self(
            requestId: $request->requestId,
            playID: strtolower(Str::after($request->username, '_')),
            username: $request->username,
            token: $request->externalToken
        );
    }

    public static function fromLogoutRequest(Request $request): self
    {
        return new self(
            requestId: $request->requestId,
            playID: strtolower(Str::after($request->username, '_')),
            token: $request->externalToken
        );
    }

    public static function fromBetRequest(Request $request): self
    {
        return new self(
            playID: strtolower(Str::after($request->username, '_')),
            token: Str::after($request->externalToken, '_'),
            extID: $request->transactionCode,
            roundID: $request->gameRoundCode,
            dateTime: $request->transactionDate,
            amount: (float) $request->amount,
            gameID: $request->gameCodeName,
            requestId: $request->requestId,
        );
    }

    public static function fromGameRoundResultRequest(Request $request): self
    {
        return new self(
            playID: strtolower(Str::after($request->username, '_')),
            extID: $request->input('pay.transactionCode'),
            roundID: $request->gameRoundCode,
            dateTime: $request->input('pay.transactionDate'),
            amount: (float) $request->input('pay.amount'),
            transactionType: $request->input('pay.type'),
            betExtID: $request->input('pay.relatedTransactionCode'),
            gameID: $request->gameCodeName,
            requestId: $request->requestId,
        );
    }
}