<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class RNGProductsNotSupportedException extends \Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 404,
            'ErrorMessage' => 'RNG products not supported'
        ]);
    }
}
