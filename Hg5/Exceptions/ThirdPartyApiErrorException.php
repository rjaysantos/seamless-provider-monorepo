<?php

namespace Providers\Hg5\Exceptions;

use Exception;
use Providers\Hg5\Hg5DateTime;
use Illuminate\Http\JsonResponse;

class ThirdPartyApiErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'status' => [
                'code' => 100,
                'message' => 'Something Wrong.',
                'datetime' => Hg5DateTime::getDateTimeNow()
            ]
        ], 200);
    }
}
