<?php

namespace Providers\Hg5\Exceptions;

use Exception;
use Providers\Hg5\Hg5DateTime;
use Illuminate\Http\JsonResponse;

class TransactionAlreadySettledException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'status' => [
                'code' => 103,
                'message' => 'Transaction service error',
                'balance' => 0.00,
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ], 200);
    }
}
