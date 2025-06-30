<?php

namespace Providers\Pla\DTO;

use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PlaRequestDTO
{
    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?string $token = null,
    ) {}

    public static function fromLogoutRequest(Request $request): self
    {
        return new self(
            playID: $request->requestId,
            username: Str::after($request->username, '_'),
            token: $request->externalToken
        );
    }
}
