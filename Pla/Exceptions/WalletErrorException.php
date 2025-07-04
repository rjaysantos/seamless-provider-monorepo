<?php

namespace Providers\Pla\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Providers\Pla\DTO\PlaRequestDTO;

class WalletErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => request()->input('requestId'),
            'error' => [
                'code' => 'INTERNAL_ERROR'
            ]
        ], 200);
    }
}
