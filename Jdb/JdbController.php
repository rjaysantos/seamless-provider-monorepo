<?php

namespace Providers\Jdb;

use Illuminate\Http\Request;
use Providers\Jdb\JdbService;
use Providers\Jdb\JdbResponse;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Jdb\Exceptions\InvalidActionException;
use Providers\Jdb\Exceptions\InvalidProviderRequestException;

class JdbController
{
    private const GET_BALANCE = 6;
    private const BET_AND_SETTLE = 8;
    private const CANCEL_BET_AND_SETTLE = 4;
    private const BET = 9;
    private const CANCEL_BET = 11;
    private const SETTLE = 10;

    public function __construct(
        private JdbService $service,
        private JdbResponse $response,
        private JdbCredentials $credentials,
        private JdbEncryption $encryption
    ) {
    }

    private function casinoRequestValidator(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != env('FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;
    }

    public function play(Request $request)
    {
        $this->casinoRequestValidator(
            request: $request,
            rules: [
                'playId' => 'required|string',
                'username' => 'required|string',
                'currency' => 'required|string|in:IDR,PHP,THB,VND,BRL,USD',
                'language' => 'required|string',
                'device' => 'required|integer',
                'gameId' => 'required|string'
            ]
        );

        $launchUrl = $this->service->getLaunchUrl(request: $request);

        return $this->response->casinoSuccess(data: $launchUrl);
    }

    public function visual(Request $request)
    {
        $this->casinoRequestValidator(
            request: $request,
            rules: [
                'play_id' => 'required|string',
                'bet_id' => 'required|string',
                'currency' => 'required|string',
                'game_id' => 'required|string'
            ]
        );

        $betDetailUrl = $this->service->getBetDetailUrl(request: $request);

        return $this->response->casinoSuccess(data: $betDetailUrl);
    }

    private function providerRequestValidator(array $request, array $rules): void
    {
        $validate = Validator::make(data: $request, rules: $rules);

        if ($validate->fails())
            throw new InvalidProviderRequestException;
    }

    public function entryPoint(Request $request, string $currency)
    {
        $this->providerRequestValidator(
            request: $request->all(),
            rules: ['x' => 'required|string']
        );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $currency);

        $decryptedRequestData = $this->encryption->decrypt(
            credentials: $credentials,
            data: $request->x
        );

        if (isset($decryptedRequestData->action) === false)
            throw new InvalidProviderRequestException;

        return match ($decryptedRequestData->action) {
            self::GET_BALANCE => $this->balance(request: $decryptedRequestData),
            self::BET_AND_SETTLE => $this->betAndSettle(request: $decryptedRequestData),
            self::CANCEL_BET_AND_SETTLE => $this->cancelBetAndSettle(request: $decryptedRequestData),
            self::BET => $this->bet(request: $decryptedRequestData),
            self::CANCEL_BET => $this->cancelBet(request: $decryptedRequestData),
            self::SETTLE => $this->settle(request: $decryptedRequestData),
            default => throw new InvalidActionException,
        };
    }

    private function balance(object $request)
    {
        $this->providerRequestValidator(
            request: (array) $request,
            rules: [
                'uid' => 'required|string',
                'currency' => 'required|string'
            ]
        );

        $balance = $this->service->getBalance(request: $request);

        return $this->response->providerSuccess(balance: $balance);
    }

    private function betAndSettle(object $request)
    {
        $this->providerRequestValidator(
            request: (array) $request,
            rules: [
                'ts' => 'required|numeric',
                'transferId' => 'required|integer',
                'uid' => 'required|string',
                'currency' => 'required|string',
                'gType' => 'required|integer',
                'mType' => 'required|integer',
                'bet' => 'required|numeric',
                'win' => 'required|numeric',
                'historyId' => 'required|string'
            ]
        );

        $balance = $this->service->betAndSettle(request: $request);

        return $this->response->providerSuccess(balance: $balance);
    }

    private function cancelBetAndSettle(object $request)
    {
        $this->providerRequestValidator(
            request: (array) $request,
            rules: [
                'ts' => 'required|numeric',
                'transferId' => 'required|numeric',
                'uid' => 'required|string',
                'currency' => 'required|string'
            ]
        );

        $this->service->cancelBetAndSettle(request: $request);
    }

    private function bet(object $request)
    {
        $this->providerRequestValidator(
            request: (array) $request,
            rules: [
                'ts' => 'required|numeric',
                'transferId' => 'required|numeric',
                'uid' => 'required|string',
                'currency' => 'required|string',
                'amount' => 'required|numeric',
                'gType' => 'required|integer',
                'mType' => 'required|integer'
            ]
        );

        $balance = $this->service->bet(request: $request);

        return $this->response->providerSuccess(balance: $balance);
    }

    private function cancelBet(object $request)
    {
        $this->providerRequestValidator(
            request: (array) $request,
            rules: [
                'ts' => 'required|numeric',
                'uid' => 'required|string',
                'currency' => 'required|string',
                'amount' => 'required|numeric',
                'refTransferIds' => 'required|array|min:1',
                'refTransferIds.*' => 'required|numeric',
            ]
        );

        $balance = $this->service->cancelBet(request: $request);

        return $this->response->providerSuccess(balance: $balance);
    }

    private function settle(object $request)
    {
        $this->providerRequestValidator(
            request: (array) $request,
            rules: [
                'ts' => 'required|numeric',
                'uid' => 'required|string',
                'amount' => 'required|numeric',
                'currency' => 'required|string',
                'refTransferIds' => 'required|array|min:1',
                'refTransferIds.*' => 'required|numeric',
                'gType' => 'required|integer',
                'mType' => 'required|integer',
                'historyId' => 'required|string'
            ]
        );

        $balance = $this->service->settle(request: $request);

        return $this->response->providerSuccess(balance: $balance);
    }
}