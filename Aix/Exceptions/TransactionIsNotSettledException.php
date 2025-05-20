<?php

namespace Providers\Aix\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionIsNotSettledException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 0
        ]);
    }
}