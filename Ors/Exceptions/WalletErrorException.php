<?php

namespace Providers\Ors\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class WalletErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'rs_code' => 'S-113',
            'rs_message' => 'internal error on the operator'
        ], 200);
    }
}
