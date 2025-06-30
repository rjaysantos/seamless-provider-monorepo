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
        public readonly ?float $amount = null,
        public readonly ?float $winAmount = null
    ) {}

    public static function fromGetBalanceRequest(Request $request)
    {
        return new self(playID: $request->uid);
    }

    public static function fromCancelSettlementRequest(Request $request): self
    {
        return new self(
            playID: $request->uid,
            roundID: $request->orderNo
        );
    }

    public static function fromSettlementRequest(Request $request): self
    {
        return new self(
            playID: $request->uid,
            dateTime: $request->timestamp,
            roundID: $request->orderNo,
            gameID: $request->gameCode,
            amount: $request->bet,
            winAmount: $request->win
        );
    }
}