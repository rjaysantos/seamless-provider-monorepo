<?php

namespace Providers\Ors;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Providers\Ors\OgSignature;
use App\Libraries\LaravelHttpClient;
use Illuminate\Support\Facades\Validator;
use Providers\Ors\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class OrsApi
{
    public function __construct(private LaravelHttpClient $http, private OgSignature $encryption)
    {
    }

    public function validateResponse(object $response, array $rules): void
    {
        $validator = Validator::make(
            data: json_decode(json_encode($response), true),
            rules: $rules
        );

        if ($validator->fails() || $response->rs_code !== 'S-100')
            throw new ThirdPartyApiErrorException;
    }

    public function enterGame(ICredentials $credentials, Request $request, string $token): string
    {
        $apiRequest = [
            'player_id' => $request->playId,
            'timestamp' => Carbon::now()->timestamp,
            'nickname' => $request->playId,
            'token' => $token,
            'lang' => $request->language,
            'game_id' => $request->gameId,
            'betlimit' => 164,
        ];

        $apiRequest['signature'] = $this->encryption->createSignatureByArray(
            arrayData: $apiRequest,
            credentials: $credentials
        );

        $headers = [
            'key' => $credentials->getPublicKey(),
            'operator-name' => $credentials->getOperatorName(),
            'content-type' => 'application/json'
        ];

        $response = $this->http->get(
            url: $credentials->getApiUrl() . '/api/v2/platform/games/launch',
            request: $apiRequest,
            headers: $headers
        );

        $this->validateResponse($response, [
            'rs_code' => 'required|string',
            'game_link' => 'required|string'
        ]);

        return $response->game_link;
    }

    public function getBettingRecords(ICredentials $credentials, string $transactionID, string $playID): string
    {
        $apiRequest = [
            'transaction_id' => $transactionID,
            'player_id' => $playID,
            'game_type_id' => 2,
        ];

        $headers = [
            'key' => $credentials->getPublicKey(),
            'operator-name' => $credentials->getOperatorName(),
            'content-type' => 'application/json'
        ];

        $response = $this->http->get(
            url: $credentials->getApiUrl() . '/api/v2/platform/transaction/history',
            request: $apiRequest,
            headers: $headers
        );

        $this->validateResponse($response, [
            'rs_code' => 'required|string',
            'records' => 'required|array',
            'records.*.result_url' => 'required|string'
        ]);

        foreach ($response->records as $record)
            return $record->result_url;
    }
}
