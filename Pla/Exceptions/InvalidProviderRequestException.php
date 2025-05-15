<?php

namespace App\GameProviders\V2\PLA\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
