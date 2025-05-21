<?php

namespace Providers\Pca;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PcaResponse
{
    private const PROVIDER_TIMEZONE = 'GMT+0';

    public function casinoSuccess(string $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'code' => 200,
            'data' => $data,
            'error' => null
        ]);
    }

    private function formatBalance(float $balance): string
    {
        $number_str = strval($balance);
        $decimal_pos = strpos($number_str, '.');

        if ($decimal_pos !== false) {

            $integer_part = substr($number_str, 0, $decimal_pos);
            $decimal_part = substr($number_str, $decimal_pos + 1);

            if (strlen($decimal_part) > 2) {
                $decimal_part = substr($decimal_part, 0, 2);
            }

            $decimal_part = str_pad($decimal_part, 2, '0', STR_PAD_RIGHT);

            $formatted_number = $integer_part . '.' . $decimal_part;
        } else {
            $formatted_number = $number_str . '.00';
        }

        return $formatted_number;
    }

    private function getDateTimeNow(): string
    {
        $currentDateTime = Carbon::now('GMT+8')->setTimezone(self::PROVIDER_TIMEZONE);

        $milliseconds = substr($currentDateTime->format('u'), 0, 3);

        return $currentDateTime->format('Y-m-d H:i:s') . '.' . $milliseconds;
    }

    public function authenticate(string $requestId, string $playID, string $currency): JsonResponse
    {
        switch ($currency) {
            case 'IDR':
                $country = 'ID';
                break;
            case 'PHP':
                $country = 'PH';
                break;
            case 'VND':
                $country = 'VN';
                break;
            case 'BRL':
                $country = 'BR';
                break;
            case 'USD':
                $country = 'US';
                break;
            case 'THB':
                $country = 'TH';
                break;
        }

        return response()->json(data: [
            "requestId" => $requestId,
            "username" => $playID,
            "currencyCode" => config('app.env') === 'PRODUCTION' ? $currency : 'CNY',
            "countryCode" => config('app.env') === 'PRODUCTION' ? $country : 'CN'
        ]);
    }

    public function getBalance(string $requestId, float $balance): JsonResponse
    {
        return response()->json(data: [
            "requestId" => $requestId,
            "balance" => [
                "real" => $this->formatBalance($balance),
                "timestamp" => $this->getDateTimeNow()
            ]
        ]);
    }

    public function healthCheck(): JsonResponse
    {
        return response()->json([], 200);
    }

    public function logout(string $requestId): JsonResponse
    {
        return response()->json(data: ["requestId" => $requestId]);
    }

    public function bet(Request $request, float $balance): JsonResponse
    {
        return response()->json(data: [
            'requestId' => $request->requestId,
            'externalTransactionCode' => $request->transactionCode,
            'externalTransactionDate' => $request->transactionDate,
            'balance' => [
                'real' => $this->formatBalance($balance),
                'timestamp' => $request->transactionDate
            ]
        ]);
    }

    public function gameRoundResult(Request $request, float $balance): JsonResponse
    {
        $transactionCode = is_null($request->pay) === true ?
            Str::substr($request->requestId, 0, 128) : $request->pay['transactionCode'];

        $transactionDate = is_null($request->pay) === true ?
            $this->getDateTimeNow() : $request->pay['transactionDate'];

        return response()->json(data: [
            "requestId" => $request->requestId,
            'externalTransactionCode' => $transactionCode,
            'externalTransactionDate' => $transactionDate,
            "balance" => [
                "real" => $this->formatBalance($balance),
                "timestamp" => $transactionDate
            ]
        ]);
    }    
}