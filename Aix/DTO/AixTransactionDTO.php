<?php

namespace Providers\Aix\DTO;

use App\DTO\PlayerDTO;
use App\Traits\TransactionDTOTrait;
use Carbon\Carbon;
use App\DTO\TransactionDTO;

class AixTransactionDTO extends TransactionDTO
{
    use TransactionDTOTrait;

    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    private static function convertProviderDateTime(string $dateTime): string
    {
        return Carbon::parse($dateTime, self::PROVIDER_API_TIMEZONE)
            ->setTimezone('GMT+8')
            ->format('Y-m-d H:i:s');
    }

    public static function fromBetRequest(AixRequestDTO $aixRequest, PlayerDTO $player): self
    {
        return new self(
            extID: $aixRequest->debitExtID,
            trxID: $aixRequest->trxID,
            playID: $player->playID,
            username: $player->username,
            webID: self::getWebID(playID: $player->playID),
            currency: $player->currency,
            gameID: $aixRequest->gameID,
            betAmount: $aixRequest->amount,
            betValid: $aixRequest->amount,
            dateTime: self::convertProviderDateTime($aixRequest->dateTime),
        );
    }

    public static function fromCreditRequest(AixRequestDTO $aixRequest, AixTransactionDTO $transaction): self
    {
        return new self(
            extID: $aixRequest->creditExtID,
            trxID: $aixRequest->trxID,
            playID: $transaction->playID,
            username: $transaction->username,
            webID: $transaction->webID,
            currency: $transaction->currency,
            gameID: $transaction->gameID,
            betWinAmount: $aixRequest->amount,
            betWinlose: $aixRequest->amount - $transaction->betAmount,
            dateTime: self::convertProviderDateTime($aixRequest->dateTime),
        );
    }

    public static function fromBonusRequest(AixRequestDTO $aixRequest, AixTransactionDTO $transaction): self
    {
        return new self(
            extID: $aixRequest->bonusExtID,
            trxID: $aixRequest->trxID,
            playID: $transaction->playID,
            username: $transaction->username,
            webID: $transaction->webID,
            currency: $transaction->currency,
            gameID: $transaction->gameID,
            betWinAmount: $aixRequest->amount,
            betWinlose: $aixRequest->amount,
            dateTime: self::convertProviderDateTime($aixRequest->dateTime),
        );
    }
}
