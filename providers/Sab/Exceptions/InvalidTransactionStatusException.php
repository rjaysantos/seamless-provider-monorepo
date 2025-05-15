<?php

namespace Providers\Sab\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidTransactionStatusException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 309,
            'msg' => 'Invalid Transaction Status',
        ]);
    }
}
