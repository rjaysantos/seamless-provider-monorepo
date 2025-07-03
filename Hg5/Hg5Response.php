<?php

namespace Providers\Hg5;

use Illuminate\Support\Str;
use Providers\Hg5\Hg5DateTime;
use Illuminate\Http\JsonResponse;
use Providers\Hg5\DTO\Hg5PlayerDTO;
use Illuminate\Support\Facades\View;
use Providers\Hg5\DTO\Hg5RequestDTO;

class Hg5Response
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

    public function visualHtml(array $data)
    {
        $path = __DIR__ . '/views/hg5_visual.blade.php';
        return View::file($path, $data);
    }

    public function balance(object $data): JsonResponse
    {
        return response()->json([
            'data' => [
                'balance' => $data->balance,
                'currency' => $data->currency
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ]);
    }

    public function authenticate(float $balance, Hg5PlayerDTO $playerDTO): JsonResponse
    {
        return response()->json([
            'data' => [
                'playerId' => $playerDTO->playID,
                'currency' => $playerDTO->currency,
                'sessionId' => Str::uuid()->toString(),
                'balance' => $balance
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ]);
    }

    public function singleTransactionResponse(float $balance, Hg5RequestDTO $requestDTO): JsonResponse
    {
        return response()->json([
            'data' => [
                'balance' => $balance,
                'currency' => $requestDTO->currency,
                'gameRound' => $requestDTO->roundID
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ]);
    }

    public function multipleTransactionResponse(array $data): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ]);
    }

    public function multiplayerTransactionResponse(float $balance, string $currency): JsonResponse
    {
        return response()->json([
            'data' => [
                'balance' => $balance,
                'currency' => $currency
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ]);
    }
}
