<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;

abstract class AbstractCasinoController
{
    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != config('app.bearer'))
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
            'gameId' => 'required|string',
            'memberIp' => 'required|string',
            'language' => 'required|string'
        ]);

        $launchUrl = $this->service->getLaunchUrl(request: $request);

        return $this->response->casinoSuccess(url: $launchUrl);
    }

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'play_id' => 'required|string',
            'bet_id' => 'required|string',
            'currency' => 'required|string'
        ]);

        $betDetailUrl = $this->service->getBetDetailUrl(request: $request);

        return $this->response->casinoSuccess(url: $betDetailUrl);
    }
}
