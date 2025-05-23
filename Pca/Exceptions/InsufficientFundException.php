<?php

namespace Providers\Pca\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InsufficientFundException extends Exception
{
    public function __construct(private Request $request) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => $this->request->requestId,
            'error' => [
                'code' => 'ERR_INSUFFICIENT_FUNDS'
            ]
        ], 200);
    }
}