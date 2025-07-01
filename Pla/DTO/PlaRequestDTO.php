<?php

namespace Providers\Pla\DTO;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PlaRequestDTO
{
    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $token = null,
        public readonly ?string $refID = null,
        public readonly ?string $roundID = null,
        public readonly ?string $dateTime = null,
        public readonly ?string $amount = null,
        public readonly ?string $gameID = null,
        public readonly ?string $requestID = null

    ) {}

    public static function fromBetRequest(Request $request): self
    {
        return new self(
            playID: strtolower(Str::after($request->username, '_')),
            token: $request->externalToken,
            refID: $request->gameRoundCode,
            roundID: $request->transactionCode,
            dateTime: $request->transactionDate,
            amount: $request->amount,
            gameID: $request->gameCodeName,
            requestID: $request->requestId,
        );
    }
}
