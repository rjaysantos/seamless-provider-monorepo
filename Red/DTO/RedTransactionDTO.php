<?php

namespace Providers\Red\DTO;

use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class RedTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+0';

    public static function bet(string $extID, RedRequestDTO $requestDTO, RedPlayerDTO $playerDTO): self
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

    public static function settle(string $extID, RedRequestDTO $requestDTO, RedTransactionDTO $betTransaction): self
    {
        return new self(
            extID: $extID,
            roundID: $requestDTO->roundID,
            playID: $betTransaction->playID,
            username: $betTransaction->username,
            webID: $betTransaction->webID,
            currency: $betTransaction->currency,
            gameID: $betTransaction->gameID,
            winAmount: $requestDTO->amount,
            betWinlose: $requestDTO->amount - $betTransaction->betAmount,
            dateTime: self::convertProviderDateTime(
                dateTime: $requestDTO->dateTime,
                providerTimezone: self::PROVIDER_API_TIMEZONE
            )
        );
    }

    public static function bonus(string $extID, RedRequestDTO $requestDTO, RedPlayerDTO $playerDTO): self
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
                dateTime: $requestDTO->dateTime,
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->amount,
        );
    }
}
