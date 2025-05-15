<?php

namespace Providers\Ors\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'rs_code' => 'S-119',
            'rs_message' => 'transaction does not existed',
        ], 200);
    }
}
