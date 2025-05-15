<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class InvalidBetAmountException extends \Exception
{
    public function __construct(protected $data = null) {}

    public function render($request): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error',
            'Balance' => $this->data,
            'AccountName' => $request->Username,
        ]);
    }
}
