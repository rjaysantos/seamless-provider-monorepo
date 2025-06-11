<?php

namespace Providers\Aix\DTO;

use Carbon\Carbon;
use App\DTO\TransactionDTO;
use Illuminate\Http\Request;
use App\Traits\TransactionDTOTrait;

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

    public static function bet(string $extID, AixRequestDTO $requestDTO, AixPlayerDTO $playerDTO): self
    {
        return new self(
            extID: $extID,
            roundID: $requestDTO->trxID,
            playID: $playerDTO->playID,
            username: $playerDTO->username,
            webID: self::getWebID(playID: $playerDTO->playID),
            currency: $playerDTO->currency,
            gameID: $requestDTO->gameID,
            betValid: $requestDTO->amount,
            betAmount: $requestDTO->amount,
            dateTime: self::convertProviderDateTime($requestDTO->dateTime),
        );
    }

    public static function settle(string $extID, AixRequestDTO $requestDTO, AixTransactionDTO $betTransactionDTO): self
    {
        return new self(
            extID: $extID,
            roundID: $betTransactionDTO->roundID,
            playID: $betTransactionDTO->playID,
            username: $betTransactionDTO->username,
            webID: $betTransactionDTO->webID,
            currency: $betTransactionDTO->currency,
            gameID: $betTransactionDTO->gameID,
            betWinlose: $requestDTO->amount - $betTransactionDTO->betAmount,
            dateTime: self::convertProviderDateTime($requestDTO->dateTime),
        );
    }

    public static function bonus(string $extID, AixRequestDTO $requestDTO, AixTransactionDTO $settleTransactionDTO): self
    {
        return new self(
            extID: $extID,
            roundID: $settleTransactionDTO->roundID,
            playID: $settleTransactionDTO->playID,
            username: $settleTransactionDTO->username,
            webID: $settleTransactionDTO->webID,
            currency: $settleTransactionDTO->currency,
            gameID: $settleTransactionDTO->gameID,
            betWinlose: $requestDTO->amount,
            dateTime: self::convertProviderDateTime($requestDTO->dateTime),
        );
    }

    public static function debitRequest(Request $request): self
    {
        return new self(
            extID: "wager-{$request->txn_id}",
            roundID: $request->txn_id,
            gameID: $request->prd_id,
            betAmount: $request->amount,
            betValid: $request->amount,
            dateTime: self::convertProviderDateTime($request->debit_time),
        );
    }

    // public static function settle(Request $request): self
    // {
    //     return new self(
    //         extID: $request->txn_id,
    //         transactionID: "payout-{$request->txn_id}",
    //         winAmount: $request->amount,
    //         updatedDateTime: self::convertProviderDateTime($request->credit_time),
    //     );
    // }

    public static function fromBonusRequest(AixRequestDTO $aixRequest, AixTransactionDTO $transaction): self
    {
        return new self(
            extID: $aixRequest->extID,
            transactionID: "bonus-{$aixRequest->extID}",
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
