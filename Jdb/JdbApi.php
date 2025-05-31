<?php

namespace Providers\Jdb;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Jdb\JdbEncryption;
use Providers\Jdb\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class JdbApi
{
    private const ACTION_LAUNCH_GAME = 21;
    private const JDB_SLOT_GAME_TYPE = 0;
    private const JDB_ARCADE_GAME_TYPE = 1;
    private const ACTION_QUERY_GAME = 54;

    public function __construct(
        private LaravelHttpClient $http,
        private JdbEncryption $encryption,
    ) {
    }

    private function isArcade(string $gameID): bool
    {
        if (preg_match('/^\d+-\d+$/', $gameID) === self::JDB_ARCADE_GAME_TYPE)
            return true;

        return false;
    }

    private function getCurrentTimestampInMilliseconds(): int
    {
        return Carbon::now()->timestamp * 1000 + floor(Carbon::now()->microsecond / 1000);
    }

    private function callApi(ICredentials $credentials, array $request): object
    {
        $apiRequest = [
            'dc' => $credentials->getDC(),
            'x' => $this->encryption->encrypt(credentials: $credentials, data: $request)
        ];

        return $this->http->post(
            url: $credentials->getApiUrl() . '/apiRequest.do',
            request: $apiRequest,
            headers: []
        );
    }

    private function validateResponse(array $response, array $rules): void
    {
        $validate = Validator::make(data: $response, rules: $rules);

        if ($validate->fails() || $response['status'] !== '0000')
            throw new ThirdPartyApiErrorException;
    }

    public function getGameLaunchUrl(
        ICredentials $credentials,
        Request $request,
        float $balance
    ): string {
        $requestData = [
            'action' => self::ACTION_LAUNCH_GAME,
            'ts' => $this->getCurrentTimestampInMilliseconds(),
            'parent' => $credentials->getParent(),
            'uid' => $request->playId,
            'balance' => $balance,
            'lang' => $request->language
        ];

        $gameID = $request->gameId;

        if ($this->isArcade(gameID: $gameID) === true) {
            $requestData['gType'] = explode(separator: '-', string: $gameID)[0];
            $requestData['mType'] = explode(separator: '-', string: $gameID)[1];
        } else {
            $requestData['gType'] = self::JDB_SLOT_GAME_TYPE;
            $requestData['mType'] = $gameID;
        }

        $response = $this->callApi(
            credentials: $credentials,
            request: $requestData
        );

        $this->validateResponse(
            response: (array) $response,
            rules: [
                'status' => 'required|string',
                'path' => 'required|string'
            ]
        );

        return $response->path;
    }

    public function queryGameResult(
        ICredentials $credentials,
        string $playID,
        string $historyID,
        string $gameID
    ): string {
        if ($this->isArcade(gameID: $gameID) === true)
            $gameID = explode(separator: '-', string: $gameID)[1];

        $requestData = [
            'action' => self::ACTION_QUERY_GAME,
            'ts' => $this->getCurrentTimestampInMilliseconds(),
            'parent' => $credentials->getParent(),
            'uid' => $playID,
            'mType' => $gameID,
            'historyId' => $historyID
        ];

        $response = $this->callApi(
            credentials: $credentials,
            request: $requestData
        );

        $this->validateResponse(
            response: json_decode(json_encode($response), true),
            rules: [
                'status' => 'required|string',
                'data' => 'required|array',
                'data.*.path' => 'required|string'
            ]
        );

        return $response->data[0]->path;
    }
}