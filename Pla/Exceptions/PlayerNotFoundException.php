<?php

namespace Providers\Pla\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PlayerNotFoundException extends Exception
{
    public function __construct(private Request $request) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => $this->request->requestId,
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ], 200);
    }
}
