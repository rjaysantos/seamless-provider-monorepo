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
        public readonly ?string $shortRoundID = null,
        public readonly ?string $dateTime = null,
    ) {}

    public static function payout(Hg5RequestDTO $requestDTO, HG5TransactionDTO $wagerTransactionDTO): self
    {
        return new self(
            extID: "payout-{$requestDTO->roundID}",
            roundID: $wagerTransactionDTO->roundID,
            playID: $wagerTransactionDTO->playID,
            username: $wagerTransactionDTO->username,
            webID: self::getWebID(playID: $wagerTransactionDTO->playID),
            currency: $wagerTransactionDTO->currency,
            gameID: $wagerTransactionDTO->gameID,
            betWinlose: $requestDTO->amount - $wagerTransactionDTO->betAmount,
            dateTime: self::convertProviderDateTime(
                dateTime: $requestDTO->dateTime,
                providerTimezone: self::PROVIDER_API_TIMEZONE
            ),
            shortRoundID: md5($requestDTO->roundID)
        );
    }
}
