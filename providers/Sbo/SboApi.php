<?php

namespace Providers\Sbo;

use Illuminate\Support\Str;
use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Sbo\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class SboApi
{
    public function __construct(private LaravelHttpClient $http) {}

    private function callApi(string $url, array $request): object
    {
        $headers = [
            'Content-Type' => 'application/json'
        ];

        return $this->http->post($url, $request, $headers);
    }

    private function validateResponseData(object $data, array $rules): void
    {
        $validate = Validator::make(
            json_decode(json_encode((array)$data), true),
            $rules
        );

        if ($validate->fails())
            throw new ThirdPartyApiErrorException();

        if ($data->error->id != 0)
            throw new ThirdPartyApiErrorException;
    }

    public function registerPlayer(ICredentials $credentials, string $username): void
    {
        $request = [
            'CompanyKey' => $credentials->getCompanyKey(),
            'ServerId'   => $credentials->getServerID(),
            'Agent'      => $credentials->getAgent(),
            'Username'   => $username
        ];

        $response = $this->callApi(
            url: $credentials->getApiUrl() . '/web-root/restricted/player/register-player.aspx',
            request: $request
        );

        $this->validateResponseData(data: $response, rules: [
            'error.id' => 'required'
        ]);
    }

    public function login(ICredentials $credentials, string $username): string
    {
        $request = [
            'CompanyKey' => $credentials->getCompanyKey(),
            'ServerId'   => $credentials->getServerID(),
            'Portfolio'  => 'SportsBook',
            'Username'   => $username
        ];

        $response = $this->callApi(
            url: $credentials->getApiUrl() . '/web-root/restricted/player/login.aspx',
            request: $request
        );

        $this->validateResponseData(data: $response, rules: [
            'url' => 'required',
            'error.id' => 'required'
        ]);

        return $response->url;
    }

    private function getPortfolio(string $transactionID): string
    {
        $portfolio = 'SportsBook';

        if (Str::contains($transactionID, 'B'))
            $portfolio = 'VirtualSports';

        if (Str::contains($transactionID, 'fkg'))
            $portfolio = 'SeamlessGame';

        if (Str::contains($transactionID, 'TK'))
            $portfolio = 'Games';

        return $portfolio;
    }

    public function getBetPayload(ICredentials $credentials, string $trxID): string
    {
        $request = [
            'CompanyKey' => $credentials->getCompanyKey(),
            'ServerId'   => $credentials->getServerID(),
            'Portfolio'  => $this->getPortfolio(transactionID: $trxID),
            'Refno'      => $trxID,
            'Language'   => 'EN'
        ];

        $response = $this->callApi(
            url: $credentials->getApiUrl() . '/web-root/restricted/report/get-bet-payload.aspx',
            request: $request
        );

        $this->validateResponseData(data: $response, rules: [
            'url' => 'required',
            'error.id' => 'required'
        ]);

        return $response->url;
    }
}
