<?php

namespace Providers\Sab;

use Carbon\Carbon;
use Illuminate\Http\JsonResponse;

class SabResponse
{
    public function casinoResponse(string $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $data,
            'error' => null
        ]);
    }

    public function visualHtml(array $sportsbookDetails)
    {
        return view('sab_visual', $sportsbookDetails);
    }

    public function balance(string $userID, float $balance)
    {
        return response()->json([
            'status' => 0,
            'userId' => $userID,
            'balance' => $balance,
            'balanceTs' => Carbon::now()->setTimezone('GMT-4')->toIso8601String()
        ]);
    }

    public function placeBet(string $refID): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'msg' => null,
            'refId' => $refID,
            'licenseeTxId' => $refID
        ]);
    }

    public function successWithBalance(float $balance): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'msg' => null,
            'balance' => $balance
        ]);
    }

    public function outstanding(object $runningTransactions): JsonResponse
    {
        return response()->json([
            'data' => $runningTransactions->data,
            'recordsTotal' => $runningTransactions->totalCount,
            'recordsFiltered' => $runningTransactions->totalCount
        ]);
    }

    public function placeBetParlay(array $transactions): JsonResponse
    {
        $data = [];
        foreach ($transactions as $transaction) {
            $data[] = [
                'refId' => $transaction['refId'],
                'licenseeTxId' => $transaction['refId']
            ];
        }

        return response()->json([
            'status' => 0,
            'txns' => $data
        ]);
    }

    public function successWithoutBalance(): JsonResponse
    {
        return response()->json([
            'status' => 0,
            'msg' => null
        ]);
    }
}
