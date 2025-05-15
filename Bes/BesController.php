<?php

namespace Providers\Bes;

use Illuminate\Http\Request;
use Providers\Bes\BesResponse;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Bes\Exceptions\InvalidProviderRequestException;

class BesController
{
    public function __construct(
        private BesService $service,
        private BesResponse $response
    ) {
    }

    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != env('FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;
    }

    private function validateProviderRequest(Request $request, array $rules): void
    {
        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails())
            throw new InvalidProviderRequestException;
    }

    public function play(Request $request)
    {
        $this->validateCasinoRequest(
            request: $request,
            rules: [
                'playId' => 'required|string',
                'username' => 'required|string',
                'currency' => 'required|string|in:IDR,PHP,THB,VND,BRL,USD',
                'language' => 'required|string',
                'gameId' => 'required|string',
                'host' => 'required|string',
            ]
        );

        $gameUrl = $this->service->getLaunchUrl(request: $request);

        return $this->response->casinoResponse(data: $gameUrl);
    }

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(
            request: $request,
            rules: [
                'play_id' => 'required|string',
                'bet_id' => 'required|string',
                'currency' => 'required|string',
            ]
        );

        $visualUrl = $this->service->getBetDetailUrl(request: $request);

        return $this->response->casinoResponse(data: $visualUrl);
    }

    public function updateGamePosition()
    {
        $this->service->updateGamePosition();

        return $this->response->casinoResponse(data: 'Success');
    }

    private function getBalance(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'uid' => 'required|string',
                'currency' => 'required|string'
            ]
        );

        $balance = $this->service->getBalance(request: $request);

        return $this->response->balance(
            action: $request->action,
            currency: $request->currency,
            balance: $balance
        );
    }

    // private function placeBet(Request $request)
    // {
    //     $this->validateProviderRequest(
    //         request: $request,
    //         rules: [
    //             'action' => 'required|int',
    //             'mode' => 'required|int',
    //             'bet' => 'required|numeric',
    //             'uid' => 'required|string',
    //             'gid' => 'required|string',
    //             'roundId' => 'required|string',
    //             'transId' => 'required|string',
    //             'ts' => 'required|int'
    //         ]
    //     );

    //     $placeBetDetails = $this->service->placeBet(request: $request);

    //     return $this->response->balance(
    //         action: $request->action,
    //         currency: $placeBetDetails->currency,
    //         balance: $placeBetDetails->balance
    //     );
    // }

    private function settleBet(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'action' => 'required|int',
                'uid' => 'required|string',
                'mode' => 'required|int',
                'gid' => 'required|string',
                'bet' => 'required|numeric',
                'win' => 'required|numeric',
                'ts' => 'required|int',
                'roundId' => 'required|string',
                'transId' => 'required|string',
            ]
        );

        $setleBetDetails = $this->service->settleBet($request);

        return $this->response->balance(
            action: $request->action,
            currency: $setleBetDetails->currency,
            balance: $setleBetDetails->balance
        );
    }

    public function entryPoint(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'action' => 'required|int|in:1,2,3'
            ]
        );

        return match ($request->action) {
            1 => $this->getBalance(request: $request),
            3 => $this->settleBet(request: $request),
            default => throw new InvalidProviderRequestException
        };
    }
}
