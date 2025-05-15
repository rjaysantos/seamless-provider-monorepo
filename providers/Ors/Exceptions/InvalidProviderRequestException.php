<?php

namespace Providers\Ors\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidProviderRequestException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'rs_code' => 'E-104',
            'rs_message' => 'invalid parameter'
        ], 200);
    }
}
