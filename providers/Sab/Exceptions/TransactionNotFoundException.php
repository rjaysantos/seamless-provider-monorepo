<?php

namespace Providers\Sab\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 504,
            'msg' => 'No Such Ticket'
        ]);
    }
}
