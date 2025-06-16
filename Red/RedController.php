<?php

namespace Providers\Red;

use Illuminate\Http\Request;
use Providers\Red\RedService;
use Providers\Red\RedResponse;
use Providers\Red\DTO\RedRequestDTO;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\AbstractCasinoController;
use Providers\Red\Exceptions\InvalidProviderRequestException;

class RedController extends AbstractCasinoController
{
    public function __construct(
        RedService $service,
        RedResponse $response
    ) {
        $this->service = $service;
        $this->response = $response;
    }

    private function validateProviderRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails())
            throw new InvalidProviderRequestException;
    }

    public function balance(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|integer',
            'prd_id' => 'required|integer'
        ]);

        $requestDTO = RedRequestDTO::fromBalanceRequest(request: $request);

        $balance = $this->service->balance(requestDTO: $requestDTO);

        return $this->response->providerSuccess(balance: $balance);
    }

    public function debit(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|integer',
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'txn_id' => 'required|string',
            'game_id' => 'required|integer',
            'debit_time' => 'required|date'
        ]);

        $requestDTO = RedRequestDTO::fromDebitRequest(request: $request);

        $balance = $this->service->wager(requestDTO: $requestDTO);

        return $this->response->providerSuccess(balance: $balance);
    }

    public function credit(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|integer',
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'txn_id' => 'required|string',
            'game_id' => 'required|integer',
            'credit_time' => 'required|date'
        ]);

        $requestDTO = RedRequestDTO::fromCreditRequest($request);

        $balance = $this->service->payout(requestDTO: $requestDTO);

        return $this->response->providerSuccess(balance: $balance);
    }

    public function bonus(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|integer',
            'amount' => 'required|integer',
            'txn_id' => 'required|string',
            'game_id' => 'required|integer'
        ]);

        $requestDTO = RedRequestDTO::fromBonusRequest($request);

        $balance = $this->service->bonus(requestDTO: $requestDTO);

        return $this->response->providerSuccess(balance: $balance);
    }
}
