<?php

namespace Providers\Hcg;

use Illuminate\Http\JsonResponse;

class HcgResponse
{
    public function casinoSuccess($data): JsonResponse
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