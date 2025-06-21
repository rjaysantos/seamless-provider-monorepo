<?php

namespace Providers\Ors\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class OrsTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public static function payout(
        string $extID,
        OrsRequestDTO $requestDTO,
        OrsTransactionDTO $transactionDTO
    ): self {
        return new self(
            extID: $extID,
            roundID: $transactionDTO->roundID,
            playID: $transactionDTO->playID,
            username: $transactionDTO->username,
            webID: self::getWebID(playID: $transactionDTO->playID),
            currency: $transactionDTO->currency,
            gameID: $transactionDTO->gameID,
            betValid: $requestDTO->amount,
            betAmount: $requestDTO->amount,
            betWinlose: $requestDTO->amount - $transactionDTO->betAmount,
            dateTime: self::convertProviderDateTime(
                dateTime: Carbon::createFromTimestamp($requestDTO->timestamp),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->amount
        );
    }
}
