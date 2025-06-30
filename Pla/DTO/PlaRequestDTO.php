<?php

namespace Providers\Pla\DTO;

use Illuminate\Http\Request;

class PlaRequestDTO
{
    public function __construct(
        public readonly ?string $requestId = null,
        public readonly ?string $username = null,
        public readonly ?string $token = null,
    ) {}

    public static function fromGetBalanceRequest(Request $request): self
    {
        return new self(
            requestId: $request->requestId,
            username: $request->username,
            token: $request->externalToken
        );
    }
}
