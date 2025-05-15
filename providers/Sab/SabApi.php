<?php

namespace Providers\Sab;

use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Sab\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class SabApi
{
    const PROVIDER_DECIMAL_ODDS = 3;

    public function __construct(private LaravelHttpClient $http) {}

    private function validateResponse(object $response, array $rules): void
    {
        $validate = Validator::make(
            data: json_decode(json_encode($response), true),
            rules: $rules
        );

        if ($validate->fails() || $response->error_code !== 0)
            throw new ThirdPartyApiErrorException;
    }

    public function createMember(ICredentials $credentials, string $username): void
    {
        $request = [
            'vendor_id' => $credentials->getVendorID(),
            'operatorId' => $credentials->getOperatorID(),
            'vendor_member_id' => $username,
            'username' => $username,
            'currency' => $credentials->getCurrency(),
            'oddstype' => self::PROVIDER_DECIMAL_ODDS
        ];

        $response = $this->http->postAsForm(
            url: $credentials->getApiUrl() . '/api/CreateMember',
            request: $request
        );

        $this->validateResponse(response: $response, rules: [
            'error_code' => 'required|int',
        ]);
    }

    public function getSabaUrl(ICredentials $credentials, string $username, int $device): string
    {
        $request = [
            'vendor_id' => $credentials->getVendorID(),
            'vendor_member_id' => $username,
            'platform' => $device,
        ];

        $response = $this->http->postAsForm($credentials->getApiUrl() . '/api/GetSabaUrl', $request);

        $this->validateResponse(response: $response, rules: [
            'error_code' => 'required|int',
            'Data' => 'required|string'
        ]);

        return $response->Data;
    }

    public function getBetDetail(ICredentials $credentials, string $transactionID): object
    {
        $request = [
            'vendor_id' => $credentials->getVendorID(),
            'trans_id' => $transactionID
        ];

        $response = $this->http->postAsForm(
            url: $credentials->getApiUrl() . '/api/GetBetDetailByTransID',
            request: $request
        );

        $this->validateResponse(response: $response, rules: [
            'error_code' => 'required|integer',
            'Data' => 'required|array',
            'Data.BetDetails' => 'sometimes|array',
            'Data.BetVirtualSportDetails' => 'sometimes|array',
            'Data.BetNumberDetails' => 'sometimes|array'
        ]);

        if (isset($response->Data->BetDetails[0]) === true)
            return $response->Data->BetDetails[0];

        if (isset($response->Data->BetVirtualSportDetails[0]) === true)
            return $response->Data->BetVirtualSportDetails[0];

        if (isset($response->Data->BetNumberDetails[0]) === true)
            return $response->Data->BetNumberDetails[0];

        throw new ThirdPartyApiErrorException;
    }
}
