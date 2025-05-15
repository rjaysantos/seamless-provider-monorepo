<?php

namespace Providers\Red;

use Illuminate\Http\JsonResponse;

class RedResponse
{
    public function casinoSuccess(string $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $data,
            'error' => null
        ]);
    }

    public function providerSuccess(float $balance): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'balance' => $balance
        ]);
    }
}
