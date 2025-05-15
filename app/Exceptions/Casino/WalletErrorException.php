<?php

namespace App\Exceptions\Casino;

use Exception;
use Illuminate\Http\JsonResponse;

class WalletErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Wallet Error'
        ]);
    }
}
