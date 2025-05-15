<?php

namespace App\GameProviders\V2\PLA\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ThirdPartyApiErrorException extends Exception
{
    public function __construct(private Request $request) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => $this->request->requestId,
            'error' => [
                'code' => 'INTERNAL_ERROR'
            ]
        ], 200);
    }
}
