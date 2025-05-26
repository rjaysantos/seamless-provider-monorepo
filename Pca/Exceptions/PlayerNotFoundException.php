<?php

namespace Providers\Pca\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PlayerNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => request()->input('requestId'),
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ], 200);
    }
}