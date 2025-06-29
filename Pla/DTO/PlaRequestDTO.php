<?php

namespace Providers\Pla\DTO;

use Illuminate\Http\Request;

class PlaRequestDTO
{
    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?string $currency = null,
        public readonly ?string $language = null,
        public readonly ?string $gameID = null,
        public readonly ?string $device = null,
    ) {}

    public static function fromPlayRequest(Request $request): self
    {
        return new self(
            playID: $request->playId,
            username: $request->username,
            currency: $request->currency,
            language: $request->language,
            gameID: $request->gameId,
            device: $request->device
        );
    }
}
