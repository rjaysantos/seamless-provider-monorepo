<?php

namespace Providers\Aix\DTO;

use App\DTO\CasinoRequestDTO;
use App\DTO\PlayerDTO;
use App\Traits\PlayerDTOTrait;

class AixPlayerDTO extends PlayerDTO
{
    use PlayerDTOTrait;

    public static function fromPlayRequestDTO(CasinoRequestDTO $casinoRequestDTO): self
    {
        return new self(
            playID: $casinoRequestDTO->playID,
            username: $casinoRequestDTO->username,
            currency: $casinoRequestDTO->currency
        );
    }
}
