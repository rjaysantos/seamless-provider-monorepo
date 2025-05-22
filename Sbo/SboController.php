<?php

namespace Providers\Sbo;

use Illuminate\Http\Request;
use Providers\Sbo\SboService;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Sbo\Exceptions\InvalidRequestException as ProviderInvalidRequestException;

class SboController
{
    public function __construct(private SboService $service, private SboResponse $response) {}

    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != env(key: 'FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;
    }

    public function play(Request $request)
    {
        $this->validateCasinoRequest(
            request: $request,
            rules: [
                'playId' => 'required|string',
                'username' => 'required|string',
                'currency' => 'required|string|in:IDR,THB,VND,BRL,USD',
                'language' => 'required|string',
                'device' => 'required|integer',
            ]
        );

        $launchUrl = $this->service->getLaunchUrl(request: $request);

        return $this->response->casinoSuccess(data: $launchUrl);
    }

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(
            request: $request,
            rules: [
                'play_id' => 'required|string',
                'bet_id' => 'sometimes',
                'txn_id' => 'required|string',
                'currency' => 'required|string',
            ]
        );

        $url = $this->service->getBetDetailUrl(request: $request);

        return $this->response->casinoSuccess(data: $url);
    }

    private function validateProviderRequest(Request $request, array $rules): void
    {
        $validate = Validator::make($request->all(),  $rules);

        if ($validate->fails())
            throw new ProviderInvalidRequestException;
    }

    public function balance(Request $request)
    {

        $this->validateProviderRequest(
            request: $request,
            rules: [
                'CompanyKey' => 'required|string',
                'Username' => 'required|string'
            ]
        );

        $balance = $this->service->getBalance(request: $request);

        return $this->response->balance(playID: $request->Username, balance: $balance);
    }

    public function deduct(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'Amount' => 'required|regex:/^\d+(\.\d{1,6})?$/',
                'TransferCode' => 'required|string',
                'BetTime' => 'required|string',
                'CompanyKey' => 'required|string',
                'Username' => 'required|string',
                'GameId' => 'required|integer',
                'ProductType' => 'required|integer',
            ]
        );

        $balance = $this->service->deduct(request: $request);

        return $this->response->deduct(request: $request, balance: $balance);
    }

    public function rollback(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'CompanyKey' => 'required|string',
                'Username' => 'required|string',
                'TransferCode' => 'required|string',
            ]
        );

        $balance = $this->service->rollback(request: $request);

        return $this->response->balance(playID: $request->Username, balance: $balance);
    }
}
