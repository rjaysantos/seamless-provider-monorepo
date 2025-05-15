<?php

namespace Providers\Red\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidProviderRequestException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'error' => 'MISSING_PARAMETER'
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
