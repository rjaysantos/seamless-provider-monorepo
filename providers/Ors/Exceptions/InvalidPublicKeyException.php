<?php

namespace Providers\Ors\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidPublicKeyException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'rs_code' => 'E-102',
            'rs_message' => 'invalid public key in header'
        ], 200);
    }
}
