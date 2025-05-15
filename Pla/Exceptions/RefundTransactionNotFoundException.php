<?php

namespace App\GameProviders\V2\PLA\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RefundTransactionNotFoundException extends Exception
{
    public function __construct(private Request $request) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => $this->request->requestId,
            'error' => [
                'code' => 'ERR_NO_BET'
            ]
        ], 200);
    }
}