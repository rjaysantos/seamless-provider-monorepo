<?php

namespace Providers\Pla\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Providers\Pla\DTO\PlaRequestDTO;

class RefundTransactionNotFoundException extends Exception
{
    public function __construct(private PlaRequestDTO $requestDTO) {}

    public function render(): JsonResponse
    {
        return response()->json([
            'requestId' => $this->requestDTO->requestId,
            'error' => [
                'code' => 'ERR_NO_BET'
            ]
        ], 200);
    }
}
