<?php

namespace Providers\Hcg\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class CannotCancelException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'code' => '105',
            'err_text' => 'Cannot cancel, transaction settled'
        ]);
    }
}
