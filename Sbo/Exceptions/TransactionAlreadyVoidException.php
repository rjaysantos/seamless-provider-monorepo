<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class TransactionAlreadyVoidException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 2002,
            'ErrorMessage' => 'Bet Already Cancelled'
        ]);
    }
}
