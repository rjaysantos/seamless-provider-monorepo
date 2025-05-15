<?php

namespace App\GameProviders\V2\PLA;

use Illuminate\Http\Request;
use App\GameProviders\V2\PLA\PlaResponse;
use Illuminate\Support\Facades\Validator;
use App\GameProviders\V2\PLA\PlaCasinoService;
use App\GameProviders\V2\PLA\PlaProviderService;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use App\GameProviders\V2\PLA\Exceptions\InvalidProviderRequestException;

class PlaController
{
    public function __construct(
        private PlaCasinoService $casinoService,
        private PlaProviderService $providerService,
        private PlaResponse $response
    ) {
    }

    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != env(key: 'FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;
    }

    private function validateProviderRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails())
            throw new InvalidProviderRequestException(request: $request);
    }

    public function play(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'playId' => 'required|string',
            'username' => 'required|string',
            'currency' => 'required|string',
            'language' => 'required|string',
            'gameId' => 'required|string',
            'device' => 'required|numeric'
        ]);

        $launchUrl = $this->casinoService->getLaunchUrl(request: $request);

        return $this->response->casinoSuccess(data: $launchUrl);
    }

    public function authenticate(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'requestId' => 'required|string',
            'username' => 'required|string',
            'externalToken' => 'required|string'
        ]);

        $currency = $this->providerService->authenticate(request: $request);

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

        $balance = $this->providerService->getBalance(request: $request);

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

        $this->providerService->logout(request: $request);

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

        $balance = $this->providerService->bet(request: $request);

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
            $balance = $this->providerService->refund(request: $request);
        else
            $balance = $this->providerService->settle(request: $request);

        return $this->response->gameRoundResult(
            request: $request,
            balance: $balance
        );
    }

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'play_id' => 'required|string',
            'bet_id' => 'required|string',
            'currency' => 'required|string'
        ]);

        $visualUrl = $this->casinoService->getBetDetail(request: $request);

        return $this->response->casinoSuccess(data: $visualUrl);
    }
}
