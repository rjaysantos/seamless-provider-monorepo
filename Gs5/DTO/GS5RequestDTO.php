<?php

namespace Providers\Gs5\DTO;

use Illuminate\Http\Request;

class GS5RequestDTO
{
    public function __construct(
        public readonly ?string $token
    ) {}

    public static function tokenRequest(Request $request): GS5RequestDTO
    {
        return new self(
            token: $request->access_token
        );
    }
}
