<?php

namespace Providers\Hcg;

use Illuminate\Http\JsonResponse;

class HcgResponse
{
    public function casinoSuccess($url): JsonResponse
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
            'code' => 0,
            'gold' => $balance
        ]);
    }

    public function gameOfflineNotification(): JsonResponse
    {
        return response()->json([
            "code" => 0
        ]);
    }
}