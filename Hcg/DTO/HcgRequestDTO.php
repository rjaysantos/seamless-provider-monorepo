<?php

namespace Providers\Hcg\DTO;

use Illuminate\Http\Request;

class HcgRequestDTO
{
    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $roundID = null,
        public readonly ?string $dateTime = null,
        public readonly ?string $gameID = null,
        public readonly ?float $betAmount = null,
        public readonly ?float $winAmount = null
    ) {}

    public static function fromGetBalanceRequest(Request $request)
    {
        return new self(playID: $request->uid);
    }

    public static function fromSettlementRequest(Request $request): self
    {
        return new self(
            playID: $request->uid,
            dateTime: $request->timestamp,
            roundID: $request->orderNo,
            gameID: $request->gameCode,
            betAmount: $request->bet,
            winAmount: $request->win
        );
    }
}