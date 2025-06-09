<?php

namespace Providers\Ygr;

use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Ygr\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class YgrApi
{
    public function __construct(private LaravelHttpClient $http) {}

    private function validateResponse(object $response): void
    {
        $validate = Validator::make(data: (array) $response, rules: [
            'ErrorCode' => 'required|integer',
            'Data' => 'required',
            'Data.*.Url' => 'required|string'
        ]);

        if ($validate->fails() || $response->ErrorCode != 0)
            throw new ThirdPartyApiErrorException;
    }

    private function getProviderLanguage(string $lang): string
    {
        switch (strtolower($lang)) {
            case 'id':
            case 'idr':
                return 'id-ID';
            case 'th':
            case 'thb':
                return 'th-TH';
            case 'vn':
            case 'vnd':
                return 'vi-VN';
            case 'br':
            case 'brl':
                return 'pt-BR';
            default:
                return 'en-US';
        }
    }

    public function launch(ICredentials $credentials, string $token, string $language): string
    {
        $apiRequest = [
            'token' => $token,
            'language' => $this->getProviderLanguage(lang: $language)
        ];

        $headers = [
            'Supplier' => $credentials->getVendorID()
        ];

        $response = $this->http->get(
            url: $credentials->getApiUrl() . '/launch',
            request: $apiRequest,
            headers: $headers
        );

        $this->validateResponse(response: $response);

        return $response->Data->Url;
    }

    public function getBetDetailUrl(ICredentials $credentials, string $transactionID, string $currency): string
    {
        $apiRequest = [
            'WagersId' => $transactionID,
            'Lang' => $this->getProviderLanguage(lang: $currency)
        ];

        $response = $this->http->post(
            url: $credentials->getApiUrl() . '/GetGameDetailUrl',
            request: $apiRequest
        );

        $this->validateResponse(response: $response);

        return $response->Data->Url;
    }
}
