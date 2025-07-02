<?php

namespace Providers\Pla\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class PlaTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+0';

    public static function wagerAndPayout(string $extID, PlaRequestDTO $requestDTO, PlaPlayerDTO $playerDTO): self
    {
        return new self(
            extID: $extID,
            roundID: $requestDTO->roundID,
            playID: $playerDTO->playID,
            username: $playerDTO->username,
            webID: self::getWebID(playID: $playerDTO->playID),
            currency: $playerDTO->currency,
            gameID: $requestDTO->gameID,
            betValid: $requestDTO->betAmount,
            betAmount: $requestDTO->betAmount,
            winAmount: $requestDTO->winAmount,
            betWinlose: $requestDTO->winAmount - $requestDTO->betAmount,
            dateTime: is_null($requestDTO->dateTime) == false ? self::convertProviderDateTime(
                dateTime: $requestDTO->dateTime,
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ) : Carbon::now('GMT+8')->format('Y-m-d H:i:s')
        );
    }
}