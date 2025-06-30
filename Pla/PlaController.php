<?php

namespace Providers\Pla;

use App\Http\Controllers\AbstractCasinoController;
use Illuminate\Http\Request;
use Providers\Pla\PlaService;
use Providers\Pla\PlaResponse;
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
            throw new InvalidProviderRequestException(request: $request);
    }

    public function authenticate(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'requestId' => 'required|string',
            'username' => 'required|string',
            'externalToken' => 'required|string'
        ]);

        $currency = $this->service->authenticate(request: $request);

        return $this->response->authenticate(
            requestId: $request->requestId,
            playID: $request->username,
            currency: $currency
        );
    }

    public function getBalance(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'requestId' => 'required|string',
            'username' => 'required|string',
            'externalToken' => 'required|string'
        ]);

        $balance = $this->service->getBalance(request: $request);

        return $this->response->getBalance(requestId: $request->requestId, balance: $balance);
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

        $balance = $this->service->bet(request: $request);

        return $this->response->bet(request: $request, balance: $balance);
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

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'play_id' => 'required|string',
            'bet_id' => 'required|string',
            'currency' => 'required|string|in:IDR,PHP,THB,VND,USD,MYR'
        ]);

        $visualUrl = $this->service->getBetDetail(request: $request);

        return $this->response->casinoSuccess(data: $visualUrl);
    }
}
