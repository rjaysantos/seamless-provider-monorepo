<?php

namespace Providers\Sbo\Exceptions;

use Illuminate\Http\JsonResponse;

class TransactionAlreadyVoidException extends \Exception
{
    public function __construct(protected $data = null) {}

    public function render($request): JsonResponse
    {
        return response()->json([
            'ErrorCode' => 2002,
            'ErrorMessage' => 'Bet Already Cancelled',
            'Balance' => $this->data,
            'AccountName' => $request->Username,
        ]);
    }
}
