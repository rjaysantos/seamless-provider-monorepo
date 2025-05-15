<?php

namespace Providers\Red;

use Illuminate\Http\Request;
use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Red\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class RedApi
{
    private const MOBILE_DEVICE = 0;
    private const RED_MOBILE = true;
    private const RED_DESKTOP = false;

    public function __construct(private LaravelHttpClient $http)
    {
    }

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
        Request $request,
        string $username,
        float $balance
    ): object {
        $apiRequest = [
            'user' => [
                'id' => $request->memberId,
                'name' => $username,
                'balance' => $balance,
                'language' => 'en',
                'domain_url' => $request->host,
                'currency' => $request->currency,
            ],
            'prd' => [
                'id' => $credentials->getPrdID(),
                'type' => $request->gameId,
                'is_mobile' => $request->device == self::MOBILE_DEVICE ? self::RED_MOBILE : self::RED_DESKTOP
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

    public function getBetResult(ICredentials $credentials, string $transactionID): string
    {
        $apiRequest = [
            'prd_id' => $credentials->getPrdID(),
            'txn_id' => $transactionID,
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
