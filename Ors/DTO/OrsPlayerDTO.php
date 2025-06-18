<?php

namespace Providers\Ors\DTO;

use App\DTO\CasinoRequestDTO;
use App\DTO\PlayerDTO;
use App\Traits\PlayerDTOTrait;

class OrsPlayerDTO extends PlayerDTO
{
    use PlayerDTOTrait;

    public static function fromPlayRequestDTO(CasinoRequestDTO $casinoRequestDTO): self
    {
        return new self(
            playID: $casinoRequestDTO->playID,
            username: $casinoRequestDTO->playID,
            currency: $casinoRequestDTO->currency
        );
    }
}
