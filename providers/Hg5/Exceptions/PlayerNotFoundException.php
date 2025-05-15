<?php

namespace Providers\Hg5\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Providers\Hg5\Hg5DateTime;

class PlayerNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'status' => [
                'code' => 2,
                'message' => 'Player not found.',
                'balance' => 0.00,
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ], 200);
    }
}
