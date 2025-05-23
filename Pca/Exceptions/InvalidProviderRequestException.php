<?php

namespace Providers\Pca\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class InvalidProviderRequestException extends Exception
{
    public function __construct(private Request $request) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => $this->request->requestId,
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ], 200);
    }
}