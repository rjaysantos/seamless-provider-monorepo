<?php

namespace Providers\Hcg\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientFundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'code' => '106',
            'message' => 'Balance is not enough'
        ]);
    }
}