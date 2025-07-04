<?php

namespace Providers\Pla\DTO;

use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class PlaTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+0';

    public function __construct(
        public readonly ?string $extID = null,
        public readonly ?string $roundID = null,
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?int $webID = null,
        public readonly ?string $currency = null,
        public readonly ?string $gameID = null,
        public readonly ?float $betAmount = null,
        public readonly ?float $betValid = null,
        public readonly ?float $betWinlose = 0,
        public readonly ?string $dateTime = null,
    ) {}

    public static function wager(PlaRequestDTO $requestDTO, PlaPlayerDTO $playerDTO): self
    {
        return new self(
            extID: $requestDTO->extID,
            roundID: $requestDTO->roundID,
            playID: $playerDTO->playID,
            username: $playerDTO->username,
            webID: self::getWebID(playID: $playerDTO->playID),
            currency: $playerDTO->currency,
            gameID: $requestDTO->gameID,
            betValid: $requestDTO->amount,
            betAmount: $requestDTO->amount,
            dateTime: self::convertProviderDateTime(
                dateTime: $requestDTO->dateTime,
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
        );
    }
}
