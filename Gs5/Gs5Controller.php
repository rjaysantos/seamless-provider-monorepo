<?php

namespace Providers\Gs5;

use App\Http\Controllers\AbstractCasinoController;
use Illuminate\Http\Request;
use Providers\Gs5\Gs5Service;
use Providers\Gs5\Gs5Response;
use Illuminate\Support\Facades\Validator;
use Providers\Gs5\Exceptions\InvalidProviderRequestException;

class Gs5Controller extends AbstractCasinoController
{
    public function __construct(
        Gs5Service $service,
        Gs5Response $response
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
        $this->validateProviderRequest(request: $request, rules: ['access_token' => 'required|string']);

        $balance = $this->service->getBalance(request: $request);

        return $this->response->successTransaction(balance: $balance);
    }

    public function authenticate(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: ['access_token' => 'required|string']);

        $data = $this->service->authenticate(request: $request);

        return $this->response->authenticate(data: $data);
    }

    public function result(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'access_token' => 'required|string',
            'txn_id' => 'required|string',
            'total_win' => 'required|numeric',
            'game_id' => 'required|string',
            'ts' => 'required|numeric'
        ]);

        $balance = $this->service->settle(request: $request);

        return $this->response->successTransaction(balance: $balance);
    }

    public function refund(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'access_token' => 'required|string',
            'txn_id' => 'required|string'
        ]);

        $balance = $this->service->cancel(request: $request);

        return $this->response->successTransaction(balance: $balance);
    }

    public function bet(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'access_token' => 'required|string',
            'txn_id' => 'required|string',
            'total_bet' => 'required|numeric',
            'game_id' => 'required|string',
            'ts' => 'required|numeric'
        ]);

        $balance = $this->service->bet(request: $request);

        return $this->response->successTransaction(balance: $balance);
    }
}
