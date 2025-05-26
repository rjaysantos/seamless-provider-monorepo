<?php

namespace Providers\Pca\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WalletErrorException extends Exception
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