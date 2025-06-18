<?php

namespace Providers\Ors\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;
use Providers\Ors\DTO\OrsRequestDTO;

class OrsTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public static function bet(
        string $extID,
        string $roundID,
        float $amount,
        OrsRequestDTO $requestDTO,
        OrsPlayerDTO $playerDTO
    ): self {
        return new self(
            extID: $extID,
            roundID: $roundID,
            playID: $playerDTO->playID,
            username: $playerDTO->username,
            webID: self::getWebID(playID: $playerDTO->playID),
            currency: $playerDTO->currency,
            gameID: $requestDTO->gameID,
            betValid: $amount,
            betAmount: $amount,
            dateTime: self::convertProviderDateTime(
                dateTime: Carbon::createFromTimestamp($requestDTO->dateTime)->toDateTimeString(),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
        );
    }
}
