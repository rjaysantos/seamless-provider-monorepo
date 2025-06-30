<?php

namespace Providers\Gs5\DTO;

use Carbon\Carbon;
use Illuminate\Http\Request;

class GS5RequestDTO
{
    private const PROVIDER_CURRENCY_CONVERSION = 100;

    public function __construct(
        public readonly ?string $token = null,
        public readonly ?string $roundID = null,
        public readonly ?string $amount = null,
        public readonly ?string $gameID = null,
        public readonly ?string $dateTime = null,
    ) {}

    public static function tokenRequest(Request $request): GS5RequestDTO
    {
        return new self(
            token: $request->access_token
        );
    }

    public static function fromBetRequest(Request $request): GS5RequestDTO
    {
        return new self(
            token: $request->access_token,
            roundID: $request->txn_id,
            amount: $request->total_bet / self::PROVIDER_CURRENCY_CONVERSION,
            gameID: $request->game_id,
            dateTime: $request->ts
        );
    }

    public static function fromResultRequest(Request $request): GS5RequestDTO
    {
        return new self(
            token: $request->access_token,
            roundID: $request->txn_id,
            amount: $request->total_win / self::PROVIDER_CURRENCY_CONVERSION,
            gameID: $request->game_id,
            dateTime: $request->ts
        );
    }
}
