<?php

namespace Providers\Pla\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvalidProviderRequestException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => request()->input('requestId'),
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ], 200);
    }
}
