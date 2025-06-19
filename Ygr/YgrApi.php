<?php

namespace Providers\Ygr;

use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Ygr\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;
use Providers\Ygr\DTO\YgrPlayerDTO;
use Providers\Ygr\DTO\YgrTransactionDTO;

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
        return match ($lang) {
            'id', 'IDR' => 'id-ID',
            'th', 'THB' => 'th-TH',
            'vn', 'VND' => 'vi-VN',
            'br', 'BRL' => 'pt-BR',
            default => 'en-US'
        };
    }

    public function launch(ICredentials $credentials, YgrPlayerDTO $playerDTO, string $language): string
    {
        $apiRequest = [
            'token' => $playerDTO->token,
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

    public function getBetDetailUrl(ICredentials $credentials, YgrTransactionDTO $transactionDTO): string
    {
        $apiRequest = [
            'WagersId' => $transactionDTO->roundID,
            'Lang' => $this->getProviderLanguage(lang: $transactionDTO->currency)
        ];

        $response = $this->http->post(
            url: $credentials->getApiUrl() . '/GetGameDetailUrl',
            request: $apiRequest
        );

        $this->validateResponse(response: $response);

        return $response->Data->Url;
    }
}
