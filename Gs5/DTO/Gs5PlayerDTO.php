<?php

namespace Providers\Gs5\DTO;

use App\DTO\CasinoRequestDTO;
use App\Libraries\Randomizer;

class Gs5PlayerDTO
{
    public function __construct(
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?string $currency = null,
        public readonly ?string $token = null,
        public readonly ?string $gameCode = null,
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
            username: $casinoRequestDTO->username,
            currency: $casinoRequestDTO->currency,
            gameCode: $casinoRequestDTO->gameID,
            token: app(Randomizer::class)->createToken(),
        );
    }
}
