<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class PlayerNotFoundException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist',
            'Balance' => 0,
        ]);
    }
}
