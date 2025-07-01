<?php

namespace Providers\Hg5;

use App\DTO\CasinoRequestDTO;
use Illuminate\Support\Collection;
use Providers\Hg5\DTO\Hg5PlayerDTO;
use App\Libraries\LaravelHttpClient;
use Providers\Hg5\DTO\Hg5TransactionDTO;
use Illuminate\Support\Facades\Validator;
use Providers\Hg5\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;
use Providers\Hg5\Exceptions\ThirdPartyApiErrorException as ProviderThirdPartyApiErrorException;

class Hg5Api
{
    public function __construct(private LaravelHttpClient $http) {}

    private function validateResponse(object $response, array $rules): void
    {
        $validate = Validator::make(
            data: json_decode(json_encode($response), true),
            rules: $rules
        );

        if ($validate->fails() || $response->status->code !== '0')
            throw new ThirdPartyApiErrorException;
    }

    public function getGameLink(
        ICredentials $credentials,
        Hg5PlayerDTO $playerDTO,
        CasinoRequestDTO $requestDTO
    ): object {
        $apiRequest = [
            'account' => $playerDTO->playID,
            'gamecode' => $requestDTO->gameID
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

    public function getOrderDetailLink(ICredentials $credentials, string $roundID, string $playID): string
    {
        $apiRequest = [
            'roundid' => $roundID,
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

    public function getOrderQuery(ICredentials $credentials, Hg5TransactionDTO $transactionDTO): Collection
    {
        $apiRequest = [
            'starttime' => $transactionDTO->createdAt,
            'endtime' => $transactionDTO->updatedAt,
            'page' => 1,
            'account' => $transactionDTO->playID
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

        $data = collect($response->data->list);

        return $data->where('gameroundid', $transactionDTO->roundID);
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
