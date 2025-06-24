<?php

namespace Providers\Ors\DTO;

use Illuminate\Http\Request;

class OrsRequestDTO
{
    public function __construct(
        public readonly ?string $key = null,
        public readonly ?string $playID = null,
        public readonly ?string $signature = null,
        public readonly ?string $transactionType = null,
        public readonly ?Request $rawRequest = null,
        public readonly ?int $gameID = null,
        public readonly ?float $amount = null,
        public readonly ?float $totalAmount = null,
        public readonly ?string $roundID = null,
        public readonly ?int $dateTime = null,
        public readonly ?array $transactions = []
    ) {}

    public static function fromBalanceRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            signature: $request->signature,
            rawRequest: $request
        );
    }

    public static function fromRewardRequest(Request $request): self
    {
        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            signature: $request->signature,
            gameID: $request->game_code,
            amount: $request->amount,
            roundID: $request->transaction_id,
            dateTime: $request->called_at,
            rawRequest: $request
        );
    }

    public static function fromDebitRequest(Request $request): self
    {
        foreach ($request->records as $record) {
            $transactions[] = new self(
                gameID: $request->game_id,
                amount: $record['amount'],
                roundID: $record['transaction_id'],
                dateTime: $request->called_at,
            );
        }

        return new self(
            key: $request->header('key'),
            playID: $request->player_id,
            signature: $request->signature,
            transactionType: $request->transaction_type,
            totalAmount: $request->total_amount,
            rawRequest: $request,
            transactions: $transactions
        );
    }
}
