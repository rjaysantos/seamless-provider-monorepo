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
}