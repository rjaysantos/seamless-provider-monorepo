<?php

namespace Providers\Pla\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TransactionNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => request()->input('requestId'),
            'error' => [
                'code' => 'INTERNAL_ERROR',
                'description' => 'No bet found with this gameRoundCode'
            ]
        ], 200);
    }
}
