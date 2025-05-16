<?php

namespace Providers\Jdb\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => '9999',
            'err_text' => 'Failed'
        ]);
    }
}
