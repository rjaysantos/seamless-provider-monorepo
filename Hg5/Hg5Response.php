<?php

namespace Providers\Hg5;

use Illuminate\Support\Str;
use Providers\Hg5\Hg5DateTime;
use Illuminate\Http\JsonResponse;
use Providers\Hg5\DTO\Hg5PlayerDTO;
use Illuminate\Support\Facades\View;

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

    public function balance(float $balance, Hg5PlayerDTO $playerDTO): JsonResponse
    {
        return response()->json([
            'data' => [
                'balance' => $balance,
                'currency' => $playerDTO->currency
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

    public function singleTransactionResponse(float $balance, string $currency, string $gameRound): JsonResponse
    {
        return response()->json([
            'data' => [
                'balance' => $balance,
                'currency' => $currency,
                'gameRound' => $gameRound
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
