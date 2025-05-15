<?php

namespace Providers\Gs5\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionAlreadySettledException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json(['status_code' => 2]);
    }
}