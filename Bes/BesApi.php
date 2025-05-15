<?php

namespace Providers\Bes;

use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Bes\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class BesApi
{
    public function __construct(private LaravelHttpClient $http)
    {
    }

    private function validateResponse(object $response, array $rules)
    {
        $validate = Validator::make(data: (array) $response, rules: $rules);

        if ($validate->fails() || $response->status !== 1)
            throw new ThirdPartyApiErrorException;
    }

    public function getKey(ICredentials $credentials, string $playID): string
    {
        $request = [
            'cert' => $credentials->getCert(),
            'user' => $playID,
            'extension1' => $credentials->getAgentID()
        ];
        
        $response = $this->http->postAsForm(url: $credentials->getApiUrl() . '/api/game/getKey', request: $request);

        $this->validateResponse(
            response: $response,
            rules: [
                'status' => 'required|integer',
                'returnurl' => 'required|string'
            ]
        );

        return $response->returnurl;
    }

    public function getDetailsUrl(ICredentials $credentials, string $transactionID): string
    {
        $request = [
            'cert' => $credentials->getCert(),
            'extension1' => $credentials->getAgentID(),
            'transId' => $transactionID,
            'lang' => 'en'
        ];

        $response = $this->http->postAsForm(
            url: $credentials->getApiUrl() . '/api/game/getdetailsurl',
            request: $request
        );

        $this->validateResponse(
            response: $response,
            rules: [
                'status' => 'required|integer',
                'logurl' => 'required|string',
            ]
        );

        return $response->logurl;
    }

    public function getGameList(ICredentials $credentials): array
    {
        $request = [
            'extension1' => $credentials->getAgentID(),
        ];

        $response = $this->http->postAsForm(
            url: $credentials->getApiUrl() . '/api/game/subgamelist',
            request: $request
        );

        $validate = Validator::make(
            data: json_decode(json_encode($response), true),
            rules: [
                'gamelist' => 'required|array',
                'gamelist.*.gid' => 'required|string',
                'gamelist.*.SortID' => 'required|integer'
            ]
        );

        if ($validate->fails())
            throw new ThirdPartyApiErrorException;

        return $response->gamelist;
    }

    public function updateGamePosition(ICredentials $credentials, array $gameCodes): void
    {
        $request = [
            'providerCode' => 'BES',
            'gameCode' => $gameCodes,
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $credentials->getNavigationApiBearerToken(),
        ];

        $response = $this->http->postAsForm(
            url: $credentials->getNavigationApiUrl() . '/api/games/update-game-position',
            request: $request,
            header: $headers
        );

        $validate = Validator::make(data: (array) $response, rules: [
            'code' => 'required|integer'
        ]);

        if ($validate->fails() || $response->code !== 9401)
            throw new ThirdPartyApiErrorException;
    }
}
