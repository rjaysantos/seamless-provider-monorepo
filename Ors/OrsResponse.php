<?php

namespace Providers\Ors;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Providers\Ors\DTO\OrsRequestDTO;
use Providers\Ors\DTO\OrsPlayerDTO;

class OrsResponse
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

    public function authenticate(string $token): JsonResponse
    {
        return response()->json([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_status' => 'activate',
            'token' => $token
        ]);
    }

    public function balance(float $balance, OrsPlayerDTO $playerDTO): JsonResponse
    {
        return response()->json([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_id' => $playerDTO->playID,
            'player_status' => 'activate',
            'balance' => $balance,
            'timestamp' => Carbon::now()->setTimezone('GMT+8')->timestamp,
            'currency' => $playerDTO->currency,
        ]);
    }

    public function debit(OrsRequestDTO $requestDTO, float $balance): JsonResponse
    {
        return response()->json([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_id' => $requestDTO->playID,
            'total_amount' => $requestDTO->totalAmount,
            'updated_balance' => $balance,
            'billing_at' =>  $requestDTO->rawRequest->called_at,
            'records' => $requestDTO->rawRequest->records,
        ]);
    }

    public function credit(OrsRequestDTO $requestDTO, float $balance): JsonResponse
    {
        return response()->json([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_id' => $requestDTO->playID,
            'amount' => $requestDTO->amount,
            'transaction_id' => $requestDTO->roundID,
            'updated_balance' => $balance,
            'billing_at' => $requestDTO->dateTime
        ]);
    }
}
