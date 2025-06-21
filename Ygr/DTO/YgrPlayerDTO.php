<?php

namespace Providers\Ygr\DTO;

use App\DTO\PlayerDTO;
use App\Traits\PlayerDTOTrait;

class YgrPlayerDTO extends PlayerDTO
{
    use PlayerDTOTrait;

    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?string $currency = null,
        public readonly ?string $token = null,
        public readonly ?string $gameCode = null,
    ) {}

    public static function fromDB(object $dbData): YgrPlayerDTO
    {
        return new self(
            playID: $dbData->play_id,
            username: $dbData->username,
            currency: $dbData->currency,
            token: $dbData->token,
            gameCode: $dbData->game_code
        );
    }
}
