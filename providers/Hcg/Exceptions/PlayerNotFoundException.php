<?php

namespace Providers\Hcg\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PlayerNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'code' => '101',
            'message' => 'User not exist'
        ]);
    }
}
