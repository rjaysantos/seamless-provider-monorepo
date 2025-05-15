<?php

namespace Providers\Bes\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class InvalidProviderRequestException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 1008
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
