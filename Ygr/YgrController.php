<?php

namespace Providers\Ygr;

use Illuminate\Http\Request;
use Providers\Ygr\YgrService;
use Providers\Ygr\YgrResponse;
use Providers\Ygr\DTO\YgrRequestDTO;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\AbstractCasinoController;
use Providers\Ygr\Exceptions\InvalidProviderRequestException;

class YgrController extends AbstractCasinoController
{
    public function __construct(YgrService $service, YgrResponse $response)
    {
        $this->service = $service;
        $this->response = $response;
    }

    private function validateProviderRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails() === true)
            throw new InvalidProviderRequestException;
    }

    public function authorizationConnectToken(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'connectToken' => 'required|string'
        ]);

        $requestDTO = YgrRequestDTO::tokenRequest(request: $request);

        $data = $this->service->getPlayerDetails(requestDTO: $requestDTO);

        return $this->response->authorizationConnectToken(data: $data);
    }

    public function balance(Request $request)
    {
        $this->validateProviderRequest(request: $request, rules: [
            'connectToken' => 'required|string'
        ]);

        $requestDTO = YgrRequestDTO::tokenRequest(request: $request);

        $data = $this->service->getPlayerDetails(requestDTO: $requestDTO);

        return $this->response->balance(data: $data);
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
