<?php

namespace App\Exceptions\Casino;

use Exception;
use Illuminate\Http\JsonResponse;

class ThirdPartyApiErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);
    }
}
