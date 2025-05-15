<?php

namespace Providers\Ygr\Exceptions;

use Str;
use Exception;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class InvalidProviderRequestException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json([
            'data' => [],
            'status' => [
                'code' => '201',
                'message' => 'Bad parameter',
                'dateTime' => Carbon::now()->setTimezone('GMT+8')->toRfc3339String(),
                'traceCode' => Str::uuid()->toString(),
            ]
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
