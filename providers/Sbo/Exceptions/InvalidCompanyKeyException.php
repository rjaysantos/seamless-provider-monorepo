<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class InvalidCompanyKeyException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error',
            'Balance' => 0,
        ]);
    }
}
