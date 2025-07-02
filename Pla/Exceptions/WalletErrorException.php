<?php

namespace Providers\Pla\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Providers\Pla\DTO\PlaRequestDTO;

class WalletErrorException extends Exception
{
    public function __construct(private PlaRequestDTO $requestDTO) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => $this->requestDTO->requestID,
            'error' => [
                'code' => 'INTERNAL_ERROR'
            ]
        ], 200);
    }
}
