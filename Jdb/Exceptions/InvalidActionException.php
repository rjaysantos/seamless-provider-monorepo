<?php

namespace Providers\Jdb\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidActionException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => '9007',
            'err_text' => 'Unknown action.'
        ]);
    }
}
