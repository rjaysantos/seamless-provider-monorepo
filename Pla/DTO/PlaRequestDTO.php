<?php

namespace Providers\Pla\DTO;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class PlaRequestDTO
{
    public function __construct(
        public readonly ?string $requestID = null,
        public readonly ?string $playID = null,
        public readonly ?string $gameID = null,
        public readonly ?string $transactionID = null,
        public readonly ?string $roundID = null,
        public readonly ?float $betAmount = null,
        public readonly ?float $winAmount = null,
        public readonly ?string $dateTime = null,
        public readonly ?string $transactionType = null,
        public readonly ?string $betTransactionID = null,
    ) {}

    public static function fromGameRoundResultRequest(Request $request): self
    {
        return new self(
            requestID: $request->requestId,
            playID: strtolower(Str::after($request->username, '_')),
            gameID: $request->gameCodeName,
            transactionID: isset($request->pay) == true ? $request->pay['transactionCode'] : "L-{$request->requestId}",
            roundID:  $request->gameRoundCode,
            betAmount: 0,
            winAmount: isset($request->pay) == true ? (float) $request->pay['amount'] : 0,
            dateTime: isset($request->pay) == true ? $request->pay['transactionDate'] : null,
            transactionType: isset($request->pay) == true ? $request->pay['type'] : 'LOSE',
            betTransactionID: isset($request->pay['relatedTransactionCode']) == true ? 
                $request->pay['relatedTransactionCode'] : null,
        );
    }
}