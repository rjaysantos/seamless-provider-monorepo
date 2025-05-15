<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class InsufficientFundException extends \Exception
{
    public function __construct(protected $data = null) {}

    public function render($request): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 5,
            'ErrorMessage' => 'Not enough balance',
            'Balance' => $this->data,
            'AccountName' => $request->Username,
        ]);
    }
}
