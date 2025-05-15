<?php

namespace Providers\Ors\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientFundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'rs_code' => 'S-103',
            'rs_message' => 'insufficient balance',
        ], 200);
    }
}
