<?php

namespace Providers\Sab\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientFundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 502,
            'msg' => 'Player Has Insufficient Funds'
        ]);
    }
}
