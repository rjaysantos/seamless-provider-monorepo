<?php

namespace Providers\Aix;

use App\DTO\CasinoRequestDTO;
use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Aix\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;
use Providers\Aix\DTO\AixPlayerDTO;

class AixApi
{
    private const CASINO_MOBILE = 0;

    public function __construct(private LaravelHttpClient $http) {}

    public function auth(
        ICredentials $credentials,
        AixPlayerDTO $player,
        CasinoRequestDTO $casinoRequest,
        float $balance
    ): string {
        $headers = [
            'ag-code' => $credentials->getAgCode(),
            'ag-token' => $credentials->getAgToken(),
        ];

        $request = [
            'user' => [
                'id' => $player->playID,
                'name' => $player->username,
                'balance' => $balance,
                'domain_url' => $casinoRequest->host,
                'language' => 'en',
                'currency' => $player->currency,
            ],
            'prd' => [
                'id' => $casinoRequest->gameID,
                'is_mobile' => $casinoRequest->device == self::CASINO_MOBILE ? true : false,
            ]
        ];

        $response = $this->http->post(
            url: "{$credentials->getApiUrl()}/auth",
            request: $request,
            headers: $headers
        );

        $validator = Validator::make(
            (array)$response,
            [
                'status' => 'required|integer',
                'launch_url' => 'required|string',
            ]
        );

        if ($validator->fails())
            throw new ThirdPartyApiErrorException;

        if ($response->status == 0)
            throw new ThirdPartyApiErrorException;

        return $response->launch_url;
    }
}
