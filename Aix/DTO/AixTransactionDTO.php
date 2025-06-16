<?php

namespace Providers\Aix\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class AixTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public static function wager(string $extID, AixRequestDTO $requestDTO, AixPlayerDTO $playerDTO): self
    {
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
        );
    }

    public static function payout(string $extID, AixRequestDTO $requestDTO, AixTransactionDTO $wagerTransactionDTO): self
    {
        return new self(
            extID: $extID,
            roundID: $wagerTransactionDTO->roundID,
            playID: $wagerTransactionDTO->playID,
            username: $wagerTransactionDTO->username,
            webID: $wagerTransactionDTO->webID,
            currency: $wagerTransactionDTO->currency,
            gameID: $wagerTransactionDTO->gameID,
            betWinlose: $requestDTO->amount - $wagerTransactionDTO->betAmount,
            dateTime: self::convertProviderDateTime(
                dateTime: $requestDTO->dateTime,
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->amount
        );
    }

    public static function bonus(string $extID, AixRequestDTO $requestDTO, AixPlayerDTO $playerDTO): self
    {
        return new self(
            extID: $extID,
            roundID: $requestDTO->roundID,
            playID: $playerDTO->playID,
            username: $playerDTO->username,
            webID: self::getWebID(playID: $playerDTO->playID),
            currency: $playerDTO->currency,
            gameID: $requestDTO->gameID,
            betWinlose: $requestDTO->amount,
            dateTime: self::convertProviderDateTime(
                dateTime: Carbon::now(),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->amount,
        );
    }
}
