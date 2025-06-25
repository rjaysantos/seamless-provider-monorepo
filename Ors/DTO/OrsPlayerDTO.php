<?php

namespace Providers\Ors\DTO;

use App\DTO\PlayerDTO;
use App\DTO\CasinoRequestDTO;
use App\Libraries\Randomizer;
use App\Traits\PlayerDTOTrait;

class OrsPlayerDTO extends PlayerDTO
{
    use PlayerDTOTrait;

    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?string $currency = null,
        public readonly ?string $token = null,
        public readonly ?string $gameID = null,
        public readonly ?string $providerUserID = null,
    ) {}

    public static function fromPlayRequestDTO(CasinoRequestDTO $casinoRequestDTO): self
    {
        $randomizer = app(Randomizer::class);

        return new self(
            playID: $casinoRequestDTO->playID,
            username: $casinoRequestDTO->username,
            currency: $casinoRequestDTO->currency,
            token: $randomizer->createToken(),
            gameID: $casinoRequestDTO->gameID
        );
    }
}
