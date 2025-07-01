<?php

namespace Providers\Pla;

use App\DTO\CasinoRequestDTO;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Pla\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;
use Providers\Pla\DTO\PlaPlayerDTO;

class PlaApi
{
    public function __construct(protected LaravelHttpClient $http)
    {
    }
    public const DEVICE_MOBILE = 0;

    public function validateResponse(object $response, array $rules): void
    {
        $validator = Validator::make(data: json_decode(json_encode($response), true), rules: $rules);

        if ($validator->fails() || $response->code !== 200)
            throw new ThirdPartyApiErrorException;
    }

    public function getGameLaunchUrl(ICredentials $credentials, CasinoRequestDTO $requestDTO, PlaPlayerDTO $playerDTO): string
    {
        $apiRequest = [
            'requestId' => Str::uuid()->toString(),
            'serverName' => $credentials->getServerName(),
            'username' => strtoupper("{$credentials->getKioskName()}_{$playerDTO->playID}"),
            'gameCodeName' => $requestDTO->gameID,
            'clientPlatform' => $requestDTO->device == self::DEVICE_MOBILE ? 'mobile' : 'web',
            'externalToken' => "{$credentials->getKioskName()}_{$playerDTO->token}",
            'language' => $requestDTO->lang,
            'playMode' => 1
        ];

        $headers = ['x-auth-kiosk-key' => $credentials->getKioskKey()];

        $response = $this->http->post(
            url: $credentials->getApiUrl() . '/from-operator/getGameLaunchUrl',
            request: $apiRequest,
            headers: $headers
        );

        $this->validateResponse($response, [
            'code'=> 'required|integer',
            'data'=> 'required|array',
            'data.url'=> 'required|string',
        ]);

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

        $this->validateResponse($response, [
            'code'=> 'required|integer',
            'data'=> 'required|array',
            'data.game_history_url'=> 'required|array|min:1',
        ]);

        return $response->data->game_history_url[0];
    }
}