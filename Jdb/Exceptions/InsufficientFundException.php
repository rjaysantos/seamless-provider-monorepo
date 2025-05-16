<?php

namespace Providers\Jdb\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InsufficientFundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => '6006',
            'err_text' => 'Player balance is insufficient'
        ]);
    }
}