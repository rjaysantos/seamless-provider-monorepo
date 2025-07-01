<?php

namespace Providers\Hg5\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use Illuminate\Support\Str;
use App\Traits\TransactionDTOTrait;

class Hg5TransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

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
        public readonly ?float $betWinAmount = 0,
        public readonly ?float $winAmount = 0,
        public readonly ?float $betExtID = null,
        public readonly ?float $settleExtID = null,
        public readonly ?string $updatedAt = null,
        public readonly ?string $createdAt = null,
    ) {}

    public static function fromDB(object $dbData): self
    {
        return new self(
            extID: $dbData->ext_id,
            roundID: $dbData->round_id,
            playID: $dbData->play_id,
            username: $dbData->username,
            webID: $dbData->web_id,
            currency: $dbData->currency,
            gameID: $dbData->game_code,
            betAmount: $dbData->bet_amount,
            betValid: $dbData->bet_valid,
            betWinlose: $dbData->bet_winlose,
            updatedAt: $dbData->updated_at,
            createdAt: $dbData->created_at,
        );
    }

    public static function visualDTO(Hg5TransactionDTO $transactionDTO): self
    {
        $updatedAt = Carbon::parse($transactionDTO->updatedAt)
            ->addSeconds(5)
            ->format('Y-m-d H:i:s');

        return new self(
            roundID: Str::after($transactionDTO->roundID, 'hg5-'),
            playID: $transactionDTO->playID,
            currency: $transactionDTO->currency,
            updatedAt: $updatedAt,
            createdAt: $transactionDTO->createdAt,
        );
    }
}
