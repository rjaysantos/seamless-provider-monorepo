<?php

namespace Providers\Red;

use App\Http\Controllers\AbstractCasinoController;
use Illuminate\Http\Request;
use Providers\Red\RedService;
use Providers\Red\RedResponse;
use Illuminate\Support\Facades\Validator;
use Providers\Red\DTO\RedRequestDTO;
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
            'prd_id' => 'required|integer',
            'sid' => 'required|string'
        ]);

        $balance = $this->service->balance(request: $request);

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

        $requestDTO = RedRequestDTO::fromDebitRequest($request);

        $balance = $this->service->wager(requestDTO: $requestDTO);

        return $this->response->providerSuccess(balance: $balance);
    }

    public function payout(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|integer',
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'txn_id' => 'required|string',
            'game_id' => 'required|integer',
            'credit_time' => 'required|date'
        ]);

        $balance = $this->service->payout(request: $request);

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

        $balance = $this->service->bonus(request: $request);

        return $this->response->providerSuccess(balance: $balance);
    }
}
