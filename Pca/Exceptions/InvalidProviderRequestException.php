<?php

namespace Providers\Pca\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidProviderRequestException extends Exception
{
    public function __construct(private ?string $requestId) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => $this->requestId,
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ], 200);
    }
}