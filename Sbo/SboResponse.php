<?php

namespace Providers\Sbo;

use Illuminate\Http\Request;

class SboResponse
{
    public function casinoSuccess($data)
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $data,
            'error' => null
        ]);
    }

    public function balance(Request $request, float $balance)
    {
        return response()->json([
            'AccountName' => $request->Username,
            'Balance' => $balance,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);
    }

    public function deduct(Request $request, float $balance)
    {
        return response()->json([
            'AccountName' => $request->Username,
            'Balance' => $balance,
            'BetAmount' => $request->Amount,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);
    }
}
