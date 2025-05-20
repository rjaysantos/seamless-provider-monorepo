<?php

namespace Providers\Ygr;

use Illuminate\Http\Request;
use Providers\Ygr\YgrService;
use Providers\Ygr\YgrResponse;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Ygr\Exceptions\InvalidProviderRequestException;

class YgrController
{
    public function __construct(
        private YgrService $service,
        private YgrResponse $response
    ) {
    }

    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails() === true)
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != env('FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;
    }

    public function play(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'playId' => 'required|string',
            'username' => 'required|string',
            'currency' => 'required|string|in:IDR,PHP,THB,VND,BRL,USD,MYR',
            'gameId' => 'required|string',
            'language' => 'required|string'
        ]);

        $launchUrl = $this->service->getLaunchUrl(request: $request);

        return $this->response->casinoSuccess(data: $launchUrl);
    }

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'play_id' => 'required|string',
            'bet_id' => 'required|string',
            'txn_id' => 'sometimes',
            'currency' => 'required|string|in:IDR,PHP,THB,VND,BRL,USD,MYR',
        ]);

        $visualUrl = $this->service->getBetDetail(request: $request);

        return $this->response->casinoSuccess(data: $visualUrl);
    }

    private function validateProviderRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails() === true)
            throw new InvalidProviderRequestException;
    }

    public function verifyToken(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'connectToken' => 'required|string'
        ]);

        $data = $this->service->getPlayerDetails(request: $request);

        return $this->response->verifyToken(data: $data);
    }

    public function getBalance(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'connectToken' => 'required|string'
        ]);

        $data = $this->service->getPlayerDetails(request: $request);

        return $this->response->getBalance(data: $data);
    }

    public function deleteToken(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'connectToken' => 'required|string'
        ]);

        $this->service->deleteToken(request: $request);

        return $this->response->deleteToken();
    }

    public function betAndSettle(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'connectToken' => 'required|string',
            'roundID' => 'required|string',
            'betAmount' => 'required|numeric',
            'payoutAmount' => 'required|numeric',
            'freeGame' => 'required|integer',
            'wagersTime' => 'required|string'
        ]);

        $data = $this->service->betAndSettle(request: $request);

        return $this->response->betAndSettle(data: $data);
    }
}