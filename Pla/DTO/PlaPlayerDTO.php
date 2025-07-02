<?php

namespace Providers\Pla\DTO;

use App\DTO\CasinoRequestDTO;
use App\DTO\PlayerDTO;
use App\Libraries\Randomizer;
use App\Traits\PlayerDTOTrait;

class PlaPlayerDTO extends PlayerDTO
{
    use PlayerDTOTrait;

    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?string $currency = null,
        public readonly ?string $token = null,
        public readonly ?string $providerUserID = null,
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

    public static function fromPlayRequest(CasinoRequestDTO $casinoRequest): self
    {

        $randomizer = app(Randomizer::class);

        return new self(
            playID: $casinoRequest->playID,
            username: $casinoRequest->username,
            currency: $casinoRequest->currency,
            token: $randomizer->createToken()
        );
    }
}
