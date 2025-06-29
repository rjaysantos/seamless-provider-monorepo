<?php

namespace Providers\Ors\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class OrsTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public static function bonus(string $extID, OrsRequestDTO $requestDTO, OrsPlayerDTO $playerDTO): self
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
                dateTime: Carbon::createFromTimeStamp($requestDTO->dateTime),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->amount,
        );
    }

    public static function wager(
        string $extID,
        OrsRequestDTO $requestDTO,
        OrsPlayerDTO $playerDTO
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
                dateTime: Carbon::createFromTimestamp($requestDTO->dateTime),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
        );
    }

    public static function payout(string $extID, OrsRequestDTO $requestDTO, OrsTransactionDTO $wagerTransactionDTO): self
    {
        return new self(
            extID: $extID,
            roundID: $wagerTransactionDTO->roundID,
            playID: $wagerTransactionDTO->playID,
            username: $wagerTransactionDTO->username,
            webID: self::getWebID(playID: $wagerTransactionDTO->playID),
            currency: $wagerTransactionDTO->currency,
            gameID: $wagerTransactionDTO->gameID,
            betWinlose: $requestDTO->amount - $wagerTransactionDTO->betAmount,
            dateTime: self::convertProviderDateTime(
                dateTime: Carbon::createFromTimestamp($requestDTO->dateTime),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->amount
        );
    }

    public static function cancel(string $extID, OrsRequestDTO $requestDTO, OrsPlayerDTO $playerDTO): self
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
            betWinlose: $requestDTO->amount,
            dateTime: self::convertProviderDateTime(
                dateTime: Carbon::createFromTimestamp($requestDTO->dateTime),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->amount
        );
    }
}
