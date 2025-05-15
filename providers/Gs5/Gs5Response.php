<?php

namespace Providers\Gs5;

use Illuminate\Http\JsonResponse;

class Gs5Response
{
    public function successTransaction(float $balance): JsonResponse
    {
        return response()->json([
            'status_code' => 0,
            'balance' => $balance
        ]);
    }

    public function authenticate(object $data): JsonResponse
    {
        return response()->json([
            'status_code' => 0,
            'member_id' => $data->member_id,
            'member_name' => $data->member_name,
            'balance' => $data->balance
        ]);
    }

    public function casinoSuccess(string $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $data,
            'error' => null
        ]);
    }
}
