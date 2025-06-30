<?php

namespace Providers\Ygr\DTO;

use Illuminate\Http\Request;

class YgrRequestDTO
{
    public function __construct(
        public readonly ?string $token = null,
        public readonly ?string $roundID = null,
        public readonly ?float $betAmount = null,
        public readonly ?float $payoutAmount = null,
        public readonly ?string $dateTime = null
    ) {}

    public static function tokenRequest(Request $request): self
    {
        return new self(token: $request->connectToken);
    }

    public static function fromAddGameResultRequest(Request $request): self
    {
        return new self(
            token: $request->connectToken,
            roundID: $request->roundID,
            betAmount: $request->betAmount,
            payoutAmount: $request->payoutAmount,
            dateTime: $request->wagersTime
        );
    }
}
