<?php

namespace Providers\Gs5\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class Gs5TransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';
    private const PROVIDER_CURRENCY_CONVERSION = 100;

    public static function wager(string $extID, GS5RequestDTO $requestDTO, Gs5PlayerDTO $playerDTO): self
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
                dateTime: Carbon::createFromTimestamp($requestDTO->dateTime)->format('Y-m-d H:i:s'),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
        );
    }

    public static function payout(string $extID, GS5RequestDTO $requestDTO, Gs5TransactionDTO $wagerTransactionDTO): self
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
                dateTime: Carbon::createFromTimestamp($requestDTO->dateTime)->format('Y-m-d H:i:s'),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->amount
        );
    }

    public static function cancel(Gs5TransactionDTO $wagerTransactionDTO): self
    {
        return new self(
            extID: "cancel-{$wagerTransactionDTO->roundID}",
            roundID: $wagerTransactionDTO->roundID,
            playID: $wagerTransactionDTO->playID,
            username: $wagerTransactionDTO->username,
            webID: $wagerTransactionDTO->webID,
            currency: $wagerTransactionDTO->currency,
            gameID: $wagerTransactionDTO->gameID,
            betWinlose: $wagerTransactionDTO->betAmount,
            dateTime: Carbon::now()->format('Y-m-d H:i:s'),
        );
    }
}
