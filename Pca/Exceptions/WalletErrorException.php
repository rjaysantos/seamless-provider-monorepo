<?php

namespace Providers\Pca\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WalletErrorException extends Exception
{
    public function __construct(private string $requestId) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => $this->requestId,
            'error' => [
                'code' => 'INTERNAL_ERROR'
            ]
        ], 200);
    }
}