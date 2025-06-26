<?php

namespace Providers\Ygr\DTO;

use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class YgrTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public static function wagerAndPayout(string $extID, YgrRequestDTO $requestDTO, YgrPlayerDTO $playerDTO): self
    {
        return new self(
            extID: $extID,
            roundID: $requestDTO->roundID,
            playID: $playerDTO->playID,
            username: $playerDTO->username,
            webID: self::getWebID(playID: $playerDTO->playID),
            currency: $playerDTO->currency,
            gameID: $playerDTO->gameCode,
            betAmount: $requestDTO->betAmount,
            betValid: $requestDTO->betAmount,
            betWinlose: $requestDTO->payoutAmount - $requestDTO->betAmount,
            dateTime: self::convertProviderDateTime(
                dateTime: $requestDTO->dateTime,
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->payoutAmount,
        );
    }
}
