<?php

namespace Providers\Jdb\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionStillProcessingException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => '9017',
            'err_text' => 'Work in process, please try again later'
        ]);
    }
}
