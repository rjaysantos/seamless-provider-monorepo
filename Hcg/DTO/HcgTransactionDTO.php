<?php

namespace Providers\Hcg\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;
use Providers\Hcg\DTO\HcgPlayerDTO;
use Providers\Hcg\DTO\HcgRequestDTO;

class HcgTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public static function wager(
        string $extID, 
        HcgRequestDTO $requestDTO, 
        HcgPlayerDTO $playerDTO, 
        float $betAmount,
        float $winAmount,
    ): self {   

        return new self(
            extID: "wagerpayout-{$extID}",
            roundID: $requestDTO->roundID,
            playID: $playerDTO->playID,
            username: $playerDTO->username,
            webID: self::getWebID(playID: $playerDTO->playID),
            currency: $playerDTO->currency,
            gameID: $requestDTO->gameID,
            betValid: $betAmount,
            betAmount: $betAmount,
            dateTime: self::convertProviderDateTime(
                dateTime: Carbon::createFromTimestamp($requestDTO->dateTime),
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            betWinlose: $winAmount - $betAmount ,
            winAmount: $winAmount
        );
    }
}