<?php

namespace Providers\Red\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class WalletErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'error' => 'UNKNOWN_ERROR'
        ]);
    }
}
