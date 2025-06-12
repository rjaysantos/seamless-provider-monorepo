<?php

namespace Providers\Red;

use Illuminate\Http\JsonResponse;

class RedResponse
{
    public function casinoSuccess(string $url): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $url,
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
