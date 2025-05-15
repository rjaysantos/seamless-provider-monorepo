<?php

namespace Providers\Hg5;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Hg5\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;
use Providers\Hg5\Exceptions\ThirdPartyApiErrorException as ProviderThirdPartyApiErrorException;

class Hg5Api
{
    public function __construct(private LaravelHttpClient $http)
    {
    }

    private function validateResponse(object $response, array $rules): void
    {
        $validate = Validator::make(
            data: json_decode(json_encode($response), true),
            rules: $rules
        );

        if ($validate->fails() || $response->status->code !== '0')
            throw new ThirdPartyApiErrorException;
    }

    public function getGameLink(ICredentials $credentials, string $playID, string $gameCode): object
    {
        $apiRequest = [
            'account' => $playID,
            'gamecode' => $gameCode
        ];

        $apiHeader = ['Authorization' => $credentials->getAuthorizationToken()];

        $response = $this->http->post(
            url: $credentials->getApiUrl() . '/GrandPriest/gamelink',
            request: $apiRequest,
            headers: $apiHeader
        );

        $this->validateResponse(response: $response, rules: [
            'data' => 'required|array',
            'data.url' => 'required|string',
            'data.token' => 'required|string',
            'status' => 'required|array',
            'status.code' => 'required|string'
        ]);

        return (object) [
            'url' => $response->data->url,
            'token' => $response->data->token
        ];
    }

    public function getOrderDetailLink(ICredentials $credentials, string $transactionID, string $playID): string
    {
        $apiRequest = [
            'roundid' => Str::after($transactionID, 'hg5-'),
            'account' => $playID
        ];

        $apiHeader = ['Authorization' => $credentials->getAuthorizationToken()];

        $response = $this->http->get(
            url: $credentials->getApiUrl() . '/GrandPriest/order/detail',
            request: $apiRequest,
            headers: $apiHeader
        );

        $this->validateResponse(response: $response, rules: [
            'status' => 'required|array',
            'status.message' => 'required|string',
            'status.code' => 'required|string'
        ]);

        return $response->status->message;
    }

    public function getOrderQuery(
        ICredentials $credentials,
        string $playID,
        string $startDate,
        string $endDate
    ): Collection {
        $endDate = Carbon::parse($endDate)
            ->addSeconds(5)
            ->format('Y-m-d H:i:s');

        $apiRequest = [
            'starttime' => $startDate,
            'endtime' => $endDate,
            'page' => 1,
            'account' => $playID
        ];

        $apiHeader = ['Authorization' => $credentials->getAuthorizationToken()];

        $response = $this->http->get(
            url: $credentials->getApiUrl() . '/GrandPriest/orders',
            request: $apiRequest,
            headers: $apiHeader
        );

        $this->validateResponse(response: $response, rules: [
            'data' => 'required|array',
            'data.list' => 'required|array',
            'data.list.*.gameroundid' => 'required|string',
            'data.list.*.round' => 'required|string',
            'data.list.*.win' => 'required|numeric',
            'data.list.*.bet' => 'required|numeric',
            'status' => 'required|array',
            'status.code' => 'required|string'
        ]);

        return collect($response->data->list);
    }

    public function getGameList(ICredentials $credentials): Collection
    {
        $apiHeader = ['Authorization' => $credentials->getAuthorizationToken()];

        $response = $this->http->get(
            url: $credentials->getApiUrl() . '/GrandPriest/gameList',
            request: [],
            headers: $apiHeader
        );

        $validate = Validator::make(
            data: json_decode(json_encode($response), true),
            rules: [
                'data' => 'required|array',
                'data.*.gametype' => 'required|string',
                'data.*.gamecode' => 'required|string',
                'status' => 'required|array',
                'status.code' => 'required|string'
            ]
        );

        if ($validate->fails() || $response->status->code !== '0')
            throw new ProviderThirdPartyApiErrorException;

        return collect($response->data);
    }
}
