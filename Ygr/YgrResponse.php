<?php

namespace Providers\Ygr;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;
use Providers\Ygr\DTO\YgrPlayerDTO;
use Providers\Ygr\Contracts\ICredentials;

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

    public function authorizationConnectToken(
        ICredentials $credentials,
        YgrPlayerDTO $player,
        float $balance
    ): JsonResponse {
        return $this->providerSuccessResponse(data: [
            'ownerId' => $credentials->getVendorID(),
            'parentId' => $credentials->getVendorID(),
            'gameId' => $player->gameCode,
            'userId' => $player->playID,
            'nickname' => $player->username,
            'currency' => $player->currency,
            'amount' => $this->formatToTwoDecimals(balance: $balance)
        ]);
    }

    public function getConnectTokenAmount(YgrPlayerDTO $player, float $balance): JsonResponse
    {
        return $this->providerSuccessResponse(data: [
            'currency' => $player->currency,
            'amount' => $this->formatToTwoDecimals(balance: $balance)
        ]);
    }

    public function delConnectToken(): JsonResponse
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
