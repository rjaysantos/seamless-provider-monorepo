<?php

namespace Providers\Sab\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ProviderThirdPartyApiErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 999,
            'msg' => 'System Error'
        ]);
    }
}
