<?php

namespace Providers\Red;

use App\DTO\CasinoRequestDTO;
use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Red\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;
use Providers\Red\DTO\RedPlayerDTO;
use Providers\Red\DTO\RedTransactionDTO;

class RedApi
{
    private const MOBILE_DEVICE = 0;
    private const RED_MOBILE = true;
    private const RED_DESKTOP = false;

    public function __construct(private LaravelHttpClient $http) {}

    private function callApi(string $url, ICredentials $credentials, array $request): object
    {
        $apiHeader = [
            'ag-code' => $credentials->getCode(),
            'ag-token' => $credentials->getToken(),
            'content-type' => 'application/json'
        ];

        return $this->http->post(url: $url, request: $request, headers: $apiHeader);
    }

    private function validateResponse(object $response, array $rules): void
    {
        $validate = Validator::make(data: (array) $response, rules: $rules);

        if ($validate->fails() || $response->status != 1)
            throw new ThirdPartyApiErrorException;
    }

    public function authenticate(
        ICredentials $credentials,
        CasinoRequestDTO $requestDTO,
        RedPlayerDTO $playerDTO,
        float $balance
    ): object {
        $apiRequest = [
            'user' => [
                'id' => $requestDTO->memberID,
                'name' => $playerDTO->username,
                'balance' => $balance,
                'language' => 'en',
                'domain_url' => $requestDTO->host,
                'currency' => $requestDTO->currency,
            ],
            'prd' => [
                'id' => $credentials->getPrdID(),
                'type' => $requestDTO->gameID,
                'is_mobile' => $requestDTO->device == self::MOBILE_DEVICE ? self::RED_MOBILE : self::RED_DESKTOP
            ]
        ];

        $response = $this->callApi(
            url: $credentials->getApiUrl() . '/auth',
            credentials: $credentials,
            request: $apiRequest
        );

        $this->validateResponse(response: $response, rules: [
            'status' => 'required|int',
            'user_id' => 'required|int',
            'launch_url' => 'required|string'
        ]);

        return (object) [
            'userID' => $response->user_id,
            'launchUrl' => $response->launch_url
        ];
    }

    public function getBetResult(ICredentials $credentials, RedTransactionDTO $transactionDTO): string
    {
        $apiRequest = [
            'prd_id' => $credentials->getPrdID(),
            'txn_id' => $transactionDTO->roundID,
            'lang' => 'en'
        ];

        $response = $this->callApi(
            url: $credentials->getApiUrl() . '/bet/results',
            credentials: $credentials,
            request: $apiRequest
        );

        $this->validateResponse(response: $response, rules: [
            'status' => 'required|int',
            'url' => 'required|string'
        ]);

        return $response->url;
    }
}
