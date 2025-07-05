<?php

namespace Providers\Hg5\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class Hg5TransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT-4';

    public function __construct(
        public readonly ?string $extID = null,
        public readonly ?string $roundID = null,
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?int $webID = null,
        public readonly ?string $currency = null,
        public readonly ?string $gameID = null,
        public readonly ?float $betAmount = 0,
        public readonly ?float $betValid = 0,
        public readonly ?float $betWinlose = 0,
        public readonly ?string $dateTime = null,
        public readonly ?float $winAmount = 0,
        public readonly ?string $shortRoundID = null
    ) {}

    public static function wager(
        string $extID,
        Hg5RequestDTO $requestDTO,
        Hg5PlayerDTO $playerDTO
    ): self {
        return new self(
            extID: $extID,
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
            shortRoundID: md5($requestDTO->roundID)
        );
    }
}