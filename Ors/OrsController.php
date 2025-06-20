<?php

namespace Providers\Ors;

use Illuminate\Http\Request;
use Providers\Ors\OrsService;
use Providers\Ors\OrsResponse;
use Providers\Ors\DTO\OrsRequestDTO;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Ors\Exceptions\InvalidProviderRequestException;

class OrsController
{
    public function __construct(
        private OrsService $service,
        private OrsResponse $response
    ) {}

    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != env('FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;
    }

    private function validateProviderRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: json_decode(json_encode($request->all()), true), rules: $rules);

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
                'currency' => 'required|string',
                'language' => 'required|string',
                'gameId' => 'required|string',
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
                'bet_id' => 'required|string',
                'currency' => 'required|string',
            ]
        );

        $visualUrl = $this->service->getBetDetailUrl(request: $request);

        return $this->response->casinoSuccess(data: $visualUrl);
    }

    public function authenticate(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'player_id' => 'required|string',
                'token' => 'required|string',
                'signature' => 'required|string',
            ]
        );

        $this->service->authenticate(request: $request);

        return $this->response->authenticate(token: $request->token);
    }

    public function balance(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'player_id' => 'required|string',
                'signature' => 'required|string',
            ]
        );

        $requestDTO = OrsRequestDTO::fromBalanceRequest(request: $request);

        $balance = $this->service->balance(requestDTO: $requestDTO);

        return $this->response->balance(
            balance: $balance->balance,
            playerDTO: $balance->player
        );
    }

    public function debit(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'player_id' => 'required|string',
                'total_amount' => 'required_if:transaction_type,debit|regex:/^\d+(\.\d{1,2})?$/',
                'transaction_type' => 'required|string|in:debit,rollback',
                'game_id' => 'required_if:transaction_type,debit|integer',
                'round_id' => 'required_if:transaction_type,debit|string',
                'called_at' => 'required|integer',
                'records' => 'required|array',
                'records.*.amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'records.*.transaction_id' => 'required|string',
                'signature' => 'required|string'
            ]
        );

        if ($request->transaction_type === 'debit')
            $balance = $this->service->bet(request: $request);
        else
            $balance = $this->service->rollback(request: $request);

        return $this->response->debit(request: $request, balance: $balance);
    }

    public function credit(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'player_id' => 'required|string',
                'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'transaction_id' => 'required|string',
                'transaction_type' => 'required|string|in:credit',
                'round_id' => 'required|string',
                'game_id' => 'required|integer',
                'currency' => 'required|string',
                'called_at' => 'required|integer',
                'signature' => 'required|string'
            ]
        );

        $balance = $this->service->settle(request: $request);

        return $this->response->payout(request: $request, balance: $balance);
    }

    public function reward(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'player_id' => 'required|string',
                'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'transaction_id' => 'required|string',
                'game_code' => 'required|integer',
                'called_at' => 'required|integer',
                'signature' => 'required|string'
            ]
        );

        $balance = $this->service->bonus(request: $request);

        return $this->response->payout(request: $request, balance: $balance);
    }
}
