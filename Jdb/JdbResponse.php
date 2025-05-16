<?php

namespace Providers\Jdb;

use Illuminate\Http\JsonResponse;

class JdbResponse
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
            'status' => '0000',
            'balance' => $balance,
            'err_text' => ''
        ]);
    }
}