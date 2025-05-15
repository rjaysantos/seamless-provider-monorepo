<?php

namespace Providers\Ors\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionAlreadySettledException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'rs_code' => 'S-101',
            'rs_message' => 'transaction is duplicated',
        ], 200);
    }
}
