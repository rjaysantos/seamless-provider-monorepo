<?php

namespace Providers\Hg5\DTO;

use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class Hg5TransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT-4';

    public static function wagerAndPayout(Hg5RequestDTO $requestDTO, Hg5PlayerDTO $playerDTO): self
    {
        return new self(
            extID: $requestDTO->roundID,
            roundID: $requestDTO->roundID,
            playID: $playerDTO->playID,
            username: $playerDTO->username,
            webID: self::getWebID(playID: $playerDTO->playID),
            currency: $playerDTO->currency,
            gameID: $requestDTO->gameID,
            betAmount: $requestDTO->betAmount,
            betValid: $requestDTO->betAmount,
            betWinlose: $requestDTO->winAmount - $requestDTO->betAmount,
            dateTime: self::convertProviderDateTime(
                dateTime: $requestDTO->dateTime,
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->winAmount
        );
    }
}
