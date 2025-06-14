<?php

namespace Providers\Pca\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => request()->input('requestId'),
            'error' => [
                'code' => 'INTERNAL_ERROR'
            ]
        ], 200);
    }
}