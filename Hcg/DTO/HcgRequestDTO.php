<?php

namespace Providers\Hcg\DTO;

use Illuminate\Http\Request;

class HcgRequestDTO
{
    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $roundID = null
    ) {}

    public static function fromCancelSettlementRequest(Request $request): self
    {
        return new self(
            playID: $request->uid,
            roundID: $request->orderNo
        );
    }
}