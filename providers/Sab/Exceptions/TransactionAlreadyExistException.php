<?php

namespace Providers\Sab\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionAlreadyExistException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 1,
            'msg' => 'Duplicate Transaction'
        ]);
    }
}
