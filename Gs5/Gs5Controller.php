<?php

namespace Providers\Gs5;

use Illuminate\Http\Request;
use Providers\Gs5\Gs5Service;
use Providers\Gs5\Gs5Response;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Gs5\Exceptions\InvalidProviderRequestException;

class Gs5Controller
{
    public function __construct(
        private Gs5Service $service,
        private Gs5Response $response
    ) {
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

    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != env('FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;
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

    public function play(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'playId' => 'required|string',
            'username' => 'required|string',
            'currency' => 'required|string|in:IDR,PHP,VND,USD,BRL,MYR',
            'gameId' => 'required|string',
            'language' => 'string'
        ]);

        $launchUrl = $this->service->getLaunchUrl(request: $request);

        return $this->response->casinoSuccess(data: $launchUrl);
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

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'play_id' => 'required|string',
            'bet_id' => 'required|string',
            'currency' => 'required|string|in:IDR,PHP,VND,USD,BRL,MYR'
        ]);

        $launchUrl = $this->service->getBetDetailUrl(request: $request);

        return $this->response->casinoSuccess(data: $launchUrl);
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