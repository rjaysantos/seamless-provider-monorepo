<?php

namespace Providers\Jdb\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionAlreadySettledException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => '6101',
            'err_text' => 'Can not cancel, transaction need to be settled'
        ]);
    }
}
