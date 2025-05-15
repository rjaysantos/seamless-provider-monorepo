<?php

namespace Providers\Ors\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidTokenException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'rs_code' => 'E-106',
            'rs_message' => 'token is invalid',
        ], 200);
    }
}
