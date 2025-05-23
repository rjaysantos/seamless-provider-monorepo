<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class TransactionAlreadyRollbackException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 2003,
            'ErrorMessage' => 'Bet Already Rollback'
        ]);
    }
}
