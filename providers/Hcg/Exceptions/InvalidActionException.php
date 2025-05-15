<?php

namespace Providers\Hcg\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidActionException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'code' => '9999',
            'message' => 'Action parameter error'
        ]);
    }
}
