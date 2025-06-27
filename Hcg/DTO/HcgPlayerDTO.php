<?php

namespace Providers\Hcg\DTO;

use App\DTO\PlayerDTO;
use App\Traits\PlayerDTOTrait;
use App\DTO\CasinoRequestDTO;

class HcgPlayerDTO extends PlayerDTO
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