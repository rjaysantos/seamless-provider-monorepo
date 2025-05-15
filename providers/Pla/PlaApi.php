<?php

namespace App\GameProviders\V2\PLA;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Libraries\LaravelHttpClient;
use App\GameProviders\V2\PCA\Contracts\IApi;
use App\GameProviders\V2\PLA\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException as CasinoThirdPartyApiErrorException;
use App\GameProviders\V2\PLA\Exceptions\ThirdPartyApiErrorException as ProviderThirdPartyApiErrorException;

class PlaApi implements IApi
{
    private const JACKPOT_BAN_TAG = 'gd/aixsw';

    public function __construct(protected LaravelHttpClient $http)
    {
    }

    public function getGameLaunchUrl(ICredentials $credentials, Request $request, string $token): string
    {
        $apiRequest = [
            'requestId' => Str::uuid()->toString(),
            'serverName' => $credentials->getServerName(),
            'username' => strtoupper($credentials->getKioskName() . "_{$request->playId}"),
            'gameCodeName' => $request->gameId,
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
            throw new CasinoThirdPartyApiErrorException;

        return $response->data->url;
    }
    
    public function gameRoundStatus(ICredentials $credentials, string $transactionID): string
    {
        $apiRequest = [
            'game_round' => $transactionID,
            'timezone' => 'Asia/Kuala_Lumpur'
        ];

        $headers = ['x-auth-admin-key' => $credentials->getAdminKey()];

        $response = $this->http->get(
            url: $credentials->getApiUrl() . '/reports/gameRoundStatus',
            request: $apiRequest,
            headers: $headers
        );

        if ($response->code !== 200)
            throw new CasinoThirdPartyApiErrorException;

        return $response->data->game_history_url[0];
    }

    public function setPlayerTags(ICredentials $credentials, Request $request, string $playID): void
    {
        $apiRequest = [
            'requestId' => Str::uuid()->toString(),
            'serverName' => $credentials->getServerName(),
            'username' => strtoupper($credentials->getKioskName() . "_{$playID}"),
            'tags' => [self::JACKPOT_BAN_TAG]
        ];

        $headers = ['x-auth-kiosk-key' => $credentials->getKioskKey()];

        $response = $this->http->post(
            url: $credentials->getApiUrl() . '/from-operator/setPlayerTags',
            request: $apiRequest,
            headers: $headers
        );

        if (isset($response->code) === false || $response->code !== 200)
            throw new ProviderThirdPartyApiErrorException($request);
    }
}