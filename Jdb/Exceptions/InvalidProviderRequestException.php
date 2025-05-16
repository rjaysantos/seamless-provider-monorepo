<?php

namespace Providers\Jdb\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidProviderRequestException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => '8000',
            'err_text' => 'The parameter of input error, please check your parameter is correct or not.'
        ]);
    }

    /**
     * use this to add more log context
     */
    public function context(): array
    {
        return ['error' => $this->getMessage()];
    }
}
