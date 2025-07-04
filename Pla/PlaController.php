<?php

namespace Providers\Pla;

use App\Http\Controllers\AbstractCasinoController;
use Illuminate\Http\Request;
use Providers\Pla\PlaService;
use Providers\Pla\PlaResponse;
use Providers\Pla\DTO\PlaRequestDTO;
use Illuminate\Support\Facades\Validator;
use Providers\Pla\Exceptions\InvalidProviderRequestException;

class PlaController extends AbstractCasinoController
{
    public function __construct(
        PlaService $service,
        PlaResponse $response
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

    public function authenticate(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'requestId' => 'required|string',
            'username' => 'required|string',
            'externalToken' => 'required|string'
        ]);

        $requestDTO = PlaRequestDTO::fromAuthenticateRequest(request: $request);

        $currency = $this->service->authenticate(requestDTO: $requestDTO);

        return $this->response->authenticate(requestId: $requestDTO->requestId, playID: $requestDTO->username, currency: $currency);
    }

    public function getBalance(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'requestId' => 'required|string',
            'username' => 'required|string',
            'externalToken' => 'required|string'
        ]);

        $requestDTO = PlaRequestDTO::fromGetBalanceRequest(request: $request);

        $balance = $this->service->getBalance(requestDTO: $requestDTO);

        return $this->response->getBalance(requestId: $requestDTO->requestId, balance: $balance);
    }

    public function healthCheck()
    {
        return $this->response->healthCheck();
    }

    public function logout(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'requestId' => 'required|string',
            'username' => 'required|string',
            'externalToken' => 'required|string'
        ]);

        $this->service->logout(request: $request);

        return $this->response->logout(requestId: $request->requestId);
    }

    public function bet(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'requestId' => 'required|string',
            'username' => 'required|string',
            'externalToken' => 'required|string',
            'gameRoundCode' => 'required|string',
            'transactionCode' => 'required|string',
            'transactionDate' => 'required|string',
            'amount' => 'required|string',
            'gameCodeName' => 'required|string'
        ]);

        $requestDTO = PlaRequestDTO::fromBetRequest(request: $request);

        $balance = $this->service->wagerAndPayout(requestDTO: $requestDTO);

        return $this->response->bet(requestDTO: $requestDTO, balance: $balance);
    }

    public function gameRoundResult(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'requestId' => 'required|string',
            'username' => 'required|string',
            'gameRoundCode' => 'required|string',
            'pay' => 'sometimes|array',
            'pay.transactionCode' => 'required_with:pay|string',
            'pay.transactionDate' => 'required_with:pay|string',
            'pay.type' => 'required_with:pay|string',
            'pay.amount' => 'required_with:pay|numeric',
            'pay.relatedTransactionCode' => 'required_if:pay.type,REFUND|string',
            'gameCodeName' => 'required|string',
        ]);

        if (is_null($request->pay) === false && $request->pay['type'] === 'REFUND')
            $balance = $this->service->refund(request: $request);
        else
            $balance = $this->service->settle(request: $request);

        return $this->response->gameRoundResult(request: $request, balance: $balance);
    }
}
