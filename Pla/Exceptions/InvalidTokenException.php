<?php

namespace Providers\Pla\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Providers\Pla\DTO\PlaRequestDTO;

class InvalidTokenException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => request()->input('requestId'),
            'error' => [
                'code' => 'ERR_AUTHENTICATION_FAILED'
            ]
        ], 200);
    }
}
