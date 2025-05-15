<?php

namespace Providers\Ors\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidSignatureException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'rs_code' => 'E-103',
            'rs_message' => 'invalid signature'
        ], 200);
    }
}
