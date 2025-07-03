<?php

namespace Providers\Hg5\DTO;

use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class Hg5TransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT-4';

    public static function payout(Hg5RequestDTO $requestDTO, Hg5TransactionDTO $wagerTransactionDTO): self
    {
        return new self(
            extID:  "payout-{$requestDTO->roundID}",
            roundID: $wagerTransactionDTO->roundID,
            playID: $wagerTransactionDTO->playID,
            username: $wagerTransactionDTO->username,
            webID: $wagerTransactionDTO->webID,
            currency: $wagerTransactionDTO->currency,
            gameID: $wagerTransactionDTO->gameID,
            betWinlose: $requestDTO->winAmount - $wagerTransactionDTO->betAmount,
            dateTime: self::convertProviderDateTime(
                dateTime: $requestDTO->dateTime,
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->winAmount
        );
    }
}