<?php

namespace Providers\Hcg\DTO;

use Illuminate\Http\Request;

class HcgRequestDTO
{
    public function __construct(
        public readonly ?string $playID = null
    ) {}

    public static function fromGetBalanceRequest(Request $request)
    {
        return new self(
            playID: $request->uid
        );
    }
}