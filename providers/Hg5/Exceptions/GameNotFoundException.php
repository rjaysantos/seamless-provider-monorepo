<?php

namespace Providers\Hg5\Exceptions;

use Exception;
use Providers\Hg5\Hg5DateTime;
use Illuminate\Http\JsonResponse;

class GameNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'status' => [
                'code' => 13,
                'message' => 'Game is not found.',
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ], 200);
    }
}
