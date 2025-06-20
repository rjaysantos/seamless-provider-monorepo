<?php

namespace Providers\Aix;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\AbstractCasinoController;
use Providers\Aix\DTO\AixRequestDTO;
use Providers\Aix\Exceptions\InvalidProviderRequestException;

class AixController extends AbstractCasinoController
{
    public function __construct(AixService $service, AixResponse $response)
    {
        $this->service = $service;
        $this->response = $response;
    }

    private function validateProviderRequest(Request $request, array $rules)
    {
        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails())
            throw new InvalidProviderRequestException;
    }

    public function balance(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|string',
            'prd_id' => 'required|integer'
        ]);

        $requestDTO = AixRequestDTO::fromBalanceRequest(request: $request);

        $balance = $this->service->balance(requestDTO: $requestDTO);

        return $this->response->successResponse(balance: $balance);
    }

    public function debit(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|string',
            'amount' => 'required|numeric',
            'prd_id' => 'required|integer',
            'txn_id' => 'required|string',
            'round_id' => 'required|string',
            'debit_time' => 'required|string'
        ]);

        $requestDTO = AixRequestDTO::fromDebitRequest(request: $request);

        $balance = $this->service->wager(requestDTO: $requestDTO);

        return $this->response->successResponse(balance: $balance);
    }

    public function credit(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|string',
            'amount' => 'required|numeric',
            'prd_id' => 'required|integer',
            'txn_id' => 'required|string',
            'credit_time' => 'required|string'
        ]);

        $requestDTO = AixRequestDTO::fromCreditRequest(request: $request);

        $balance = $this->service->payout(requestDTO: $requestDTO);

        return $this->response->successResponse(balance: $balance);
    }

    public function bonus(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|string',
            'amount' => 'required|numeric',
            'prd_id' => 'required|integer',
            'txn_id' => 'required|string'
        ]);

        $requestDTO = AixRequestDTO::fromBonusRequest(request: $request);

        $balance = $this->service->bonus(requestDTO: $requestDTO);

        return $this->response->successResponse(balance: $balance);
    }
}
