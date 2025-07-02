<?php

namespace Providers\Ors;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\DTO\CasinoRequestDTO;
use Providers\Ors\OgSignature;
use Providers\Ors\DTO\OrsPlayerDTO;
use App\Libraries\LaravelHttpClient;
use Providers\Ors\DTO\OrsTransactionDTO;
use Illuminate\Support\Facades\Validator;
use Providers\Ors\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class OrsApi
{
    const OG_GAME_TYPE_ID_SLOT = 2;

    public function __construct(private LaravelHttpClient $http, private OgSignature $encryption) {}

    public function validateResponse(object $response, array $rules): void
    {
        $validator = Validator::make(
            data: json_decode(json_encode($response), true),
            rules: $rules
        );

        if ($validator->fails() || $response->rs_code !== 'S-100')
            throw new ThirdPartyApiErrorException;
    }

    public function gamesLaunch(
        ICredentials $credentials,
        OrsPlayerDTO $playerDTO,
        CasinoRequestDTO $casinoRequest,
    ): string {
        $apiRequest = [
            'player_id' => $playerDTO->playID,
            'timestamp' => Carbon::now()->timestamp,
            'nickname' => $playerDTO->playID,
            'token' => $playerDTO->token,
            'lang' => $casinoRequest->lang,
            'game_id' => $casinoRequest->gameID,
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

    public function transactionHistory(ICredentials $credentials, OrsTransactionDTO $transactionDTO): string
    {
        $apiRequest = [
            'transaction_id' => $transactionDTO->roundID,
            'player_id' => $transactionDTO->playID,
            'game_type_id' => self::OG_GAME_TYPE_ID_SLOT,
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
