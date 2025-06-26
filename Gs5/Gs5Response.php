<?php

namespace Providers\Gs5;

use Illuminate\Http\JsonResponse;
use Providers\Gs5\DTO\Gs5PlayerDTO;

class Gs5Response
{
    public function success(float $balance): JsonResponse
    {
        return response()->json([
            'status_code' => 0,
            'balance' => $balance
        ]);
    }

    public function authenticate(Gs5PlayerDTO $playerDTO, float $balance): JsonResponse
    {
        return response()->json([
            'status_code' => 0,
            'member_id' => $playerDTO->playID,
            'member_name' => $playerDTO->username,
            'balance' => $balance
        ]);
    }

    public function casinoSuccess(string $url): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $url,
            'error' => null
        ]);
    }
}
