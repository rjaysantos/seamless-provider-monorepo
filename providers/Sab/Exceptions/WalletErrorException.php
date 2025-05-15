<?php

namespace Providers\Sab\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class WalletErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 901,
            'msg' => 'Database Error'
        ]);
    }
}
