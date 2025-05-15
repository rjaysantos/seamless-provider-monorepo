<?php

namespace Providers\Hcg\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidSignatureException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'code' => '207',
            'message' => 'Sign error'
        ]);
    }
}
