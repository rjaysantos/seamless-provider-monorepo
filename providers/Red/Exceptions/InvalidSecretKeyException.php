<?php

namespace Providers\Red\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidSecretKeyException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'error' => 'ACCESS_DENIED'
        ]);
    }
}
