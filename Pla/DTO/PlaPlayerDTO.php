<?php

namespace Providers\Pla\DTO;

use App\DTO\PlayerDTO;
use App\Traits\PlayerDTOTrait;

class PlaPlayerDTO extends PlayerDTO
{
    use PlayerDTOTrait;

    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?string $currency = null,
        public readonly ?string $token = null,
    ) {}

    public static function fromDB(object $dbData): self
    {
        return new self(
            playID: $dbData->play_id,
            username: $dbData->username,
            currency: $dbData->currency,
            token: $dbData->token,
        );
    }
}
