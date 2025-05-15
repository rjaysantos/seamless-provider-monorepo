<?php

namespace Providers\Hcg\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class WalletErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'code' => '9999',
            'message' => 'Wallet error'
        ]);
    }
}
