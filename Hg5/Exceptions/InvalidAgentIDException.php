<?php

namespace Providers\Hg5\Exceptions;

use Exception;
use Providers\Hg5\Hg5DateTime;
use Illuminate\Http\JsonResponse;

class InvalidAgentIDException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'status' => [
                'code' => 31,
                'message' => "Currency does not match Agent's currency.",
                'balance' => 0.00,
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ], 200);
    }
}
