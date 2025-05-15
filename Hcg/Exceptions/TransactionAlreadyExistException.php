<?php

namespace Providers\Hcg\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionAlreadyExistException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'code' => '102',
            'message' => 'Duplicate order number'
        ]);
    }
}