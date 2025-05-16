<?php

namespace Providers\Jdb\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class WalletErrorException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => '9015',
            'err_text' => 'Data does not exist.'
        ]);
    }
}
