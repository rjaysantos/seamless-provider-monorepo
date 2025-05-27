<?php

namespace Providers\Pca;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Pca\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class PcaApi
{
    const PROVIDER_GMT8_TIMEZONE = 'Asia/Kuala_Lumpur';

    public function __construct(protected LaravelHttpClient $http)
    {
    }

    public function getGameLaunchUrl(ICredentials $credentials, Request $request, string $token): string
    {
        $apiRequest = [
            'requestId' => Str::uuid()->toString(),
            'serverName' => $credentials->getServerName(),
            'username' => strtoupper($credentials->getKioskName() . "_{$request->playId}"),
            'gameCodeName' => 'ubal',
            'clientPlatform' => $request->device == 0 ? 'mobile' : 'web',
            'externalToken' => $token,
            'language' => $request->language,
            'playMode' => 1
        ];

        $headers = ['x-auth-kiosk-key' => $credentials->getKioskKey()];

        $response = $this->http->post(
            url: $credentials->getApiUrl() . '/from-operator/getGameLaunchUrl',
            request: $apiRequest,
            headers: $headers
        );

        if (isset($response->code) === false || $response->code !== 200)
            throw new ThirdPartyApiErrorException;

        return $response->data->url;
    }

    public function gameRoundStatus(ICredentials $credentials, string $transactionID): string
    {
        $apiRequest = [
            'game_round' => $transactionID,
            'timezone' => self::PROVIDER_GMT8_TIMEZONE
        ];

        $headers = ['x-auth-admin-key' => $credentials->getAdminKey()];

        $response = $this->http->get(
            url: $credentials->getApiUrl() . '/reports/gameRoundStatus',
            request: $apiRequest,
            headers: $headers
        );

        $validator = Validator::make(data: json_decode(json_encode($response), true), rules: [
            'code' => 'required|integer',
            'data' => 'required|array',
            'data.game_history_url' => 'required|array',
            'data.game_history_url.*' => 'required|string'
        ]);

        if ($validator->fails() || $response->code !== 200)
            throw new ThirdPartyApiErrorException;

        return $response->data->game_history_url[0];
    }
}