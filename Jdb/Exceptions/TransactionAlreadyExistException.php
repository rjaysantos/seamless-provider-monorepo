<?php

namespace Providers\Jdb\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionAlreadyExistException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => '9011',
            'err_text' => 'Duplicate transactions.'
        ]);
    }
}