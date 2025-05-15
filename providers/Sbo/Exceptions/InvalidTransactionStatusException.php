<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class InvalidTransactionStatusException extends \Exception
{
    public function __construct(protected $data = null) {}

    public function render($request): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 5003,
            'ErrorMessage' => 'Bet Already Settled or Cancelled',
            'Balance' => $this->data,
            'AccountName' => $request->Username,
        ]);
    }
}
