<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class InvalidRequestException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);
    }
}
