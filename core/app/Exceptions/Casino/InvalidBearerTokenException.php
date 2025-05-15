<?php

namespace App\Exceptions\Casino;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidBearerTokenException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ], 401);
    }
}
