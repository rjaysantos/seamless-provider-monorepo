<?php

namespace Providers\Ygr;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class YgrResponse
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

    private function providerSuccessResponse(array $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'status' => [
                'code' => '0',
                'message' => 'Success',
                'dateTime' => Carbon::now()->setTimezone('GMT+8')->toRfc3339String(),
                'traceCode' => Str::uuid()->toString()
            ]
        ]);
    }

    private function formatToTwoDecimals(float $balance): float
    {
        return (float) number_format($balance, 2, '.', '');
    }

    public function authorizationConnectToken(object $data): JsonResponse
    {
        return $this->providerSuccessResponse(data: [
            'ownerId' => $data->ownerId,
            'parentId' => $data->parentId,
            'gameId' => $data->gameId,
            'userId' => $data->userId,
            'nickname' => $data->nickname,
            'currency' => $data->currency,
            'amount' => $this->formatToTwoDecimals(balance: $data->balance)
        ]);
    }

    public function getBalance(object $data): JsonResponse
    {
        return $this->providerSuccessResponse(data: [
            'currency' => $data->currency,
            'amount' => $this->formatToTwoDecimals(balance: $data->balance)
        ]);
    }

    public function deleteToken(): JsonResponse
    {
        return $this->providerSuccessResponse(data: []);
    }

    public function betAndSettle(object $data): JsonResponse
    {
        return $this->providerSuccessResponse(data: [
            'balance' => $this->formatToTwoDecimals(balance: $data->balance),
            'currency' => $data->currency
        ]);
    }
}
