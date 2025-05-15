<?php

namespace Providers\Sab\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidKeyException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);
    }
}
