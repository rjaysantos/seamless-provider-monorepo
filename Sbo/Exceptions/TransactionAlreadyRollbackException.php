<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class TransactionAlreadyRollbackException extends \Exception
{
    public function __construct(protected $data = null) {}

    public function render($request): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 2003,
            'ErrorMessage' => 'Bet Already Rollback',
            'Balance' => $this->data,
            'AccountName' => $request->Username,
        ]);
    }
}
