<?php

namespace Providers\Hg5\DTO;

use App\DTO\TransactionDTO;
use App\Traits\TransactionDTOTrait;

class HG5TransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT-4';

    public function __construct(
        public readonly ?string $extID = null,
        public readonly ?string $roundID = null,
        public readonly ?string $playID = null,
        public readonly ?string $username = null,
        public readonly ?int $webID = null,
        public readonly ?string $currency = null,
        public readonly ?string $gameID = null,
        public readonly ?float $betAmount = 0,
        public readonly ?float $betValid = 0,
        public readonly ?float $betWinlose = 0,
        public readonly ?string $dateTime = null,
        public readonly ?string $shortenRoundID = null,
        public readonly ?float $winAmount = 0,
    ) {}

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
            winAmount: $requestDTO->winAmount,
            shortenRoundID: md5($requestDTO->roundID)
        );
    }
}