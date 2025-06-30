<?php

namespace Providers\Ors;

use Illuminate\Http\Request;
use Providers\Ors\OrsService;
use Providers\Ors\OrsResponse;
use Providers\Ors\DTO\OrsRequestDTO;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\AbstractCasinoController;
use Providers\Ors\Exceptions\InvalidProviderRequestException;

class OrsController extends AbstractCasinoController
{
    public function __construct(OrsService $service, OrsResponse $response)
    {
        $this->service = $service;
        $this->response = $response;
    }

    private function validateProviderRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: json_decode(json_encode($request->all()), true), rules: $rules);

        if ($validate->fails())
            throw new InvalidProviderRequestException;
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

        $requestDTO = OrsRequestDTO::fromAuthenticateRequest(request: $request);

        $this->service->authenticate(requestDTO: $requestDTO);

        return $this->response->authenticate(token: $requestDTO->token);
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

        $balanceResponse = $this->service->balance(requestDTO: $requestDTO);

        return $this->response->balance(
            balance: $balanceResponse->balance,
            playerDTO: $balanceResponse->player
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

        $requestDTO = OrsRequestDTO::fromDebitRequest(request: $request);

        if ($requestDTO->transactionType === 'debit')
            $balance = $this->service->wager(requestDTO: $requestDTO);
        else
            $balance = $this->service->rollback(request: $request);

        return $this->response->debit(requestDTO: $requestDTO, balance: $balance);
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

        $requestDTO = OrsRequestDTO::fromCreditRequest(request: $request);

        $balance = $this->service->settle(requestDTO: $requestDTO);

        return $this->response->credit(requestDTO: $requestDTO, balance: $balance);
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

        $requestDTO = OrsRequestDTO::fromRewardRequest(request: $request);

        $balance = $this->service->bonus(requestDTO: $requestDTO);

        return $this->response->payout(request: $request, balance: $balance);
    }
}
