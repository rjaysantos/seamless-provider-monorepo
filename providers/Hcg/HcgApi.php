<?php

namespace Providers\Hcg;

use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Hcg\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class HcgApi
{
    public function __construct(private LaravelHttpClient $http, private HcgEncryption $encryption)
    {
    }

    private function callAPI(ICredentials $credentials, array $requestData): object
    {
        $apiRequest = [
            'lang' => 'en',
            'x' => $this->encryption->encrypt(credentials: $credentials, data: $requestData)
        ];

        return $this->http->post(url: $credentials->getApiUrl(), request: $apiRequest);
    }

    private function validateResponse(object $response, array $rules): void
    {
        $validator = Validator::make(
            data: json_decode(json_encode($response), true),
            rules: $rules
        );

        if ($validator->fails() || $response->returnCode !== '0000')
            throw new ThirdPartyApiErrorException;
    }

    public function userRegistrationInterface(ICredentials $credentials, string $playID): void
    {
        $requestData = [
            'action' => 'register',
            'appID' => $credentials->getAppID(),
            'appSecret' => $credentials->getAppSecret(),
            'uid' => $playID
        ];

        $response = $this->callAPI(credentials: $credentials, requestData: $requestData);

        $this->validateResponse(
            response: $response,
            rules: [
                'returnCode' => 'required|string'
            ]
        );
    }

    public function userLoginInterface(ICredentials $credentials, string $playID, string $gameCode): string
    {
        $requestData = [
            'action' => 'login',
            'appID' => $credentials->getAppID(),
            'appSecret' => $credentials->getAppSecret(),
            'uid' => $playID,
            'gameCode' => $gameCode
        ];

        $response = $this->callAPI(credentials: $credentials, requestData: $requestData);

        $this->validateResponse(
            response: $response,
            rules: [
                'returnCode' => 'required|string',
                'data' => 'required|array',
                'data.path' => 'required_with:data|string'
            ]
        );

        return $response->data->path;
    }
}