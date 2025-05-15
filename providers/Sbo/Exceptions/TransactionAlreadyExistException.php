<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class TransactionAlreadyExistException extends \Exception
{
    public function __construct(protected $data = null) {}

    public function render($request): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 5003,
            'ErrorMessage' => 'Bet With Same RefNo Exists',
            'Balance' => $this->data,
            'AccountName' => $request->Username,
        ]);
    }
}
