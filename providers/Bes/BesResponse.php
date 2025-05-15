<?php

namespace Providers\Bes;

use Illuminate\Http\JsonResponse;

class BesResponse
{
    public function casinoResponse(string $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $data,
            'error' => null
        ]);
    }

    public function balance(int $action, string $currency, float $balance): JsonResponse
    {
        return response()->json([
            'action' => $action,
            'status' => 1,
            'currency' => $currency,
            'balance' => $balance
        ]);
    }
}
