<?php

namespace Providers\Hcg;

use App\Http\Controllers\AbstractCasinoController;
use Illuminate\Http\Request;
use Providers\Hcg\HcgService;
use Providers\Hcg\HcgResponse;
use Providers\Hcg\HcgEncryption;
use Illuminate\Support\Facades\Validator;
use Providers\Hcg\Exceptions\InvalidActionException;
use App\Exceptions\Casino\InvalidBearerTokenException;
use Providers\Hcg\Exceptions\InvalidSignatureException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Hcg\Exceptions\InvalidProviderRequestException;

class HcgController extends AbstractCasinoController
{
    private const GET_BALANCE = 1;
    private const SETTLEMENT = 2;
    private const CANCEL_SETTLEMENT = 3;
    private const GAME_OFFLINE_NOTIFICATION = 4;

    public function __construct(
        HcgService $service,
        HcgResponse $response,
        private HcgCredentials $credentials,
        private HcgEncryption $encryption
    ) {
        $this->service = $service;
        $this->response = $response;
        $this->credentials = $credentials;
        $this->encryption = $encryption;
    }

    private function validateProviderRequest(Request $request, array $rules)
    {
        $validation = Validator::make(data: $request->all(), rules: $rules);

        if ($validation->fails())
            throw new InvalidProviderRequestException;
    }

    public function entryPoint(Request $request, string $currency)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'sign' => 'required|string',
                'action' => 'required|integer'
            ]
        );

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $currency);
        $createdSignature = $this->encryption->createSignature(credentials: $credentials, data: $request->all());

        if ($createdSignature !== $request->sign)
            throw new InvalidSignatureException;

        return match ($request->action) {
            self::GET_BALANCE => $this->getBalance(request: $request),
            self::SETTLEMENT => $this->settlement(request: $request),
            self::CANCEL_SETTLEMENT => $this->cancelSettlement(request: $request),
            self::GAME_OFFLINE_NOTIFICATION => $this->gameOfflineNotification(),
            default => throw new InvalidActionException
        };
    }

    private function getBalance(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'uid' => 'required|string'
            ]
        );

        $balance = $this->service->getBalance(request: $request);

        return $this->response->providerSuccess(balance: $balance);
    }

    private function settlement(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'uid' => 'required|string',
                'timestamp' => 'required|int',
                'orderNo' => 'required|string',
                'gameCode' => 'required|string',
                'bet' => 'required|numeric',
                'win' => 'required|numeric'
            ]
        );

        $balance = $this->service->betAndSettle(request: $request);

        return $this->response->providerSuccess(balance: $balance);
    }

    private function cancelSettlement(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'uid' => 'required|string',
                'orderNo' => 'required|string'
            ]
        );

        $this->service->cancelBetAndSettle(request: $request);
    }

    private function gameOfflineNotification()
    {
        return $this->response->gameOfflineNotification();
    }
}