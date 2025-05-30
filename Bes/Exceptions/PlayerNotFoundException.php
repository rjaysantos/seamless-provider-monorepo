<?php

namespace Providers\Bes\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PlayerNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 1004
        ]);
    }
}
