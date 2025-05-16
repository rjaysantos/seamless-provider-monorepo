<?php

namespace Providers\Jdb\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class PlayerNotFoundException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => '7501',
            'err_text' => 'User ID cannot be found.'
        ]);
    }
}
