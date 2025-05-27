<?php

namespace Providers\Pca\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidTokenException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => request()->input('requestId'),
            'error' => [
                'code' => 'ERR_AUTHENTICATION_FAILED'
            ]
        ], 200);
    }
}