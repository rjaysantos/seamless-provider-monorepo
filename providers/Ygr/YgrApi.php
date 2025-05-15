<?php

namespace Providers\Ygr;

use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Ygr\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class YgrApi
{
    public function __construct(private LaravelHttpClient $http)
    {
    }

    private function responseValidator(object $response): void
    {
        $validate = Validator::make(data: (array) $response, rules: [
            'ErrorCode' => 'required|integer',
            'Data' => 'required',
            'Data.*.Url' => 'required|string'
        ]);

        if ($validate->fails() || $response->ErrorCode != 0)
            throw new ThirdPartyApiErrorException;
    }

    public function launch(ICredentials $credentials, string $token): string
    {
        $apiRequest = [
            'token' => $token,
            'language' => 'en-US'
        ];

        $headers = [
            'Supplier' => $credentials->getVendorID()
        ];

        $response = $this->http->get(
            url: $credentials->getApiUrl() . '/launch',
            request: $apiRequest,
            headers: $headers
        );

        $this->responseValidator(response: $response);

        return $response->Data->Url;
    }

    public function getBetDetailUrl(ICredentials $credentials, string $transactionID): string
    {
        $apiRequest = ['WagersId' => $transactionID];

        $response = $this->http->post(
            url: $credentials->getApiUrl() . '/GetGameDetailUrl',
            request: $apiRequest
        );

        $this->responseValidator(response: $response);

        return $response->Data->Url;
    }
}