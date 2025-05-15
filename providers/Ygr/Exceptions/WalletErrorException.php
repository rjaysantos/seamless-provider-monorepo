<?php

namespace Providers\Ygr\Exceptions;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\JsonResponse;

class WalletErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'data' => [],
            'status' => [
                'code' => '103',
                'message' => 'API failed',
                'dateTime' => Carbon::now()->setTimezone('GMT+8')->toRfc3339String(),
                'traceCode' => Str::uuid()->toString(),
            ]
        ]);
    }
}
