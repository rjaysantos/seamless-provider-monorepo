<?php

namespace App\Exceptions\Casino;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidCasinoRequestException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'invalid request format'
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
