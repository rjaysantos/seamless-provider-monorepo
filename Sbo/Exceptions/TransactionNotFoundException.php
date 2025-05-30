<?php

namespace Providers\Sbo\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class TransactionNotFoundException extends Exception
{
    public function __construct(protected $data = null)
    {
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 6,
            'ErrorMessage' => 'Bet not exists',
            'Balance' => $this->data,
            'AccountName' => $request->Username
        ]);
    }
}
