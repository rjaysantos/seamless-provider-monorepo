<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class WalletException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);
    }
}
