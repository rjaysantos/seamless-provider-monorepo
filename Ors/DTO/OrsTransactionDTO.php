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

    public static function payout(string $extID, OrsRequestDTO $requestDTO, OrsTransactionDTO $transactionDTO): self 
    {
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
                dateTime: Carbon::createFromTimestamp($requestDTO->dateTime),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            winAmount: $requestDTO->amount
        );
    }
}