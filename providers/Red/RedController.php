<?php

namespace Providers\Red;

use Illuminate\Http\Request;
use Providers\Red\RedService;
use Providers\Red\RedResponse;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Red\Exceptions\InvalidProviderRequestException;

class RedController
{
    public function __construct(
        private RedService $service,
        private RedResponse $response
    ) {
    }

    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != env('FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;
    }

    public function play(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'playId' => 'required|string',
            'memberId' => 'required|integer',
            'username' => 'required|string',
            'host' => 'required|string',
            'currency' => 'required|string',
            'device' => 'required|integer',
            'gameId' => 'required|string'
        ]);

        $launchUrl = $this->service->getLaunchUrl(request: $request);

        return $this->response->casinoSuccess(data: $launchUrl);
    }

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'play_id' => 'required|string',
            'bet_id' => 'required|string',
            'currency' => 'required|string'
        ]);

        $betDetailUrl = $this->service->getBetDetailUrl(request: $request);

        return $this->response->casinoSuccess(data: $betDetailUrl);
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

        $balance = $this->service->getBalance(request: $request);

        return $this->response->providerSuccess(balance: $balance);
    }

    public function wager(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'user_id' => 'required|integer',
            'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
            'txn_id' => 'required|string',
            'game_id' => 'required|integer',
            'debit_time' => 'required|date'
        ]);

        $balance = $this->service->bet(request: $request);

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

        $balance = $this->service->settle(request: $request);

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
