<?php

namespace Providers\Ors\DTO;

use App\DTO\CasinoRequestDTO;
use App\DTO\PlayerDTO;
use App\Traits\PlayerDTOTrait;

class OrsPlayerDTO extends PlayerDTO
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
    
    public static function fromPlayRequestDTO(CasinoRequestDTO $casinoRequestDTO): self
    {
        return new self(
            playID: $casinoRequestDTO->playID,
            username: $casinoRequestDTO->playID,
            currency: $casinoRequestDTO->currency
        );
    }
}
