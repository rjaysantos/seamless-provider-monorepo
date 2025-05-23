<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class TransactionAlreadySettledException extends \Exception
{
    public function __construct(protected $data = null) {}

    public function render($request): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 2001,
            'ErrorMessage' => 'Bet Already Settled',
            'Balance' => $this->data,
            'AccountName' => $request->Username,
        ]);
    }
}
