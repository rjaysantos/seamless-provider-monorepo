<?php

namespace Providers\Ygr\DTO;

use Illuminate\Http\Request;

class YgrRequestDTO
{
    public function __construct(public readonly ?string $token = null) {}

    public static function tokenRequest(Request $request): self
    {
        return new self(token: $request->connectToken);
    }
}
