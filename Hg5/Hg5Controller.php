<?php

namespace Providers\Hg5;

use Illuminate\Http\Request;
use Providers\Hg5\Hg5Response;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Hg5\Exceptions\InvalidProviderRequestException;

class Hg5Controller
{
    public function __construct(
        private Hg5Service $service,
        private Hg5Response $response
    ) {
    }

    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make(data: $request->all(), rules: $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        if ($request->bearerToken() != env('FEATURE_TEST_TOKEN'))
            throw new InvalidBearerTokenException;
    }

    public function play(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'playId' => 'required|string',
            'username' => 'required|string',
            'currency' => 'required|string|in:IDR,PHP,THB,VND,USD,MYR',
            'gameId' => 'required|string',
        ]);

        $launchUrl = $this->service->getLaunchUrl(request: $request);

        return $this->response->casinoSuccess(data: $launchUrl);
    }

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(request: $request, rules: [
            'play_id' => 'required|string',
            'bet_id' => 'required|string',
            'txn_id' => 'sometimes',
            'currency' => 'required|string|in:IDR,PHP,THB,VND,USD,MYR',
        ]);

        $visualUrl = $this->service->getBetDetailUrl(request: $request);

        return $this->response->casinoSuccess(data: $visualUrl);
    }

    public function visualHtml(string $playID, string $trxID)
    {
        $betDetailData = $this->service->getBetDetailData(encryptedPlayID: $playID, encryptedTrxID: $trxID);

        return $this->response->visualHtml(data: $betDetailData);
    }

    public function visualFishGame(Request $request)
    {
        $validate = Validator::make(data: $request->all(), rules: [
            'trxID' => 'required|string',
            'playID' => 'required|string',
            'currency' => 'required|string|in:IDR,PHP,THB,VND,USD,MYR',
        ]);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;

        $betDetailData = $this->service->getFishGameDetailUrl(request: $request);

        return $this->response->casinoSuccess(data: $betDetailData);
    }

    private function validateProviderRequest(array $request, array $rules): void
    {
        $validate = Validator::make(data: $request, rules: $rules);

        if ($validate->fails())
            throw new InvalidProviderRequestException;
    }

    public function balance(Request $request)
    {
        $this->validateProviderRequest(request: $request->all(), rules: [
            'playerId' => 'required|string',
            'agentId' => 'required|int'
        ]);

        $data = $this->service->getBalance(request: $request);

        return $this->response->balance(data: $data);
    }

    public function authenticate(Request $request)
    {
        $this->validateProviderRequest(request: $request->all(), rules: [
            'launchToken' => 'required|string',
            'agentId' => 'required|int',
            'gameId' => 'required|string'
        ]);

        $data = $this->service->authenticate(request: $request);

        return $this->response->authenticate(data: $data);
    }

    public function withdrawAndDeposit(Request $request)
    {
        $this->validateProviderRequest(request: $request->all(), rules: [
            'playerId' => 'required|string',
            'agentId' => 'required|int',
            'withdrawAmount' => 'required|numeric', // WagerAmount
            'depositAmount' => 'required|numeric', // PayoutAmount
            'currency' => 'required|string',
            'gameCode' => 'required|string',
            'gameRound' => 'required|string',
            'eventTime' => 'required|string',
            'extra' => 'sometimes|array',
            'extra.slot' => 'required_with:extra|array',
            'extra.slot.mainGameRound' => 'sometimes|string',
        ]);

        $balance = $this->service->betAndSettle(request: $request);

        return $this->response->singleTransactionResponse(
            balance: $balance,
            currency: $request->currency,
            gameRound: $request->gameRound
        );
    }

    public function withdraw(Request $request)
    {
        $this->validateProviderRequest(request: $request->all(), rules: [
            'playerId' => 'required|string',
            'agentId' => 'required|int',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'gameCode' => 'required|string',
            'gameRound' => 'required|string',
            'eventTime' => 'required|string'
        ]);

        $balance = $this->service->bet(request: $request);

        return $this->response->singleTransactionResponse(
            balance: $balance,
            currency: $request->currency,
            gameRound: $request->gameRound
        );
    }

    public function deposit(Request $request)
    {
        $this->validateProviderRequest(request: $request->all(), rules: [
            'playerId' => 'required|string',
            'agentId' => 'required|int',
            'amount' => 'required|numeric',
            'currency' => 'required|string',
            'gameCode' => 'required|string',
            'gameRound' => 'required|string',
            'eventTime' => 'required|string'
        ]);

        $balance = $this->service->settle(request: $request);

        return $this->response->singleTransactionResponse(
            balance: $balance,
            currency: $request->currency,
            gameRound: $request->gameRound
        );
    }

    public function multipleWithdraw(Request $request)
    {
        $this->validateProviderRequest(
            request: json_decode(json_encode($request->all()), true),
            rules: [
                'datas' => 'required|array',
                'datas.*.playerId' => 'required|string',
                'datas.*.agentId' => 'required|int',
                'datas.*.amount' => 'required|numeric',
                'datas.*.currency' => 'required|string',
                'datas.*.gameCode' => 'required|string',
                'datas.*.gameRound' => 'required|string',
                'datas.*.eventTime' => 'required|string'
            ]
        );

        $data = $this->service->multipleBet(request: $request);

        return $this->response->multipleTransactionResponse(data: $data);
    }

    public function multipleDeposit(Request $request)
    {
        $this->validateProviderRequest(
            request: json_decode(json_encode($request->all()), true),
            rules: [
                'datas' => 'required|array',
                'datas.*.playerId' => 'required|string',
                'datas.*.agentId' => 'required|int',
                'datas.*.amount' => 'required|numeric',
                'datas.*.currency' => 'required|string',
                'datas.*.gameCode' => 'required|string',
                'datas.*.gameRound' => 'required|string',
                'datas.*.eventTime' => 'required|string'
            ]
        );

        $data = $this->service->multipleSettle(request: $request);

        return $this->response->multipleTransactionResponse(data: $data);
    }

    public function rollout(Request $request)
    {
        $this->validateProviderRequest(request: $request->all(), rules: [
            'playerId' => 'required|string',
            'agentId' => 'required|int',
            'currency' => 'required|string',
            'amount' => 'required|numeric',
            'gameCode' => 'required|string',
            'gameRound' => 'required|string',
            'eventTime' => 'required|string'
        ]);

        $balance = $this->service->multiplayerBet(request: $request);

        return $this->response->multiplayerTransactionResponse(
            balance: $balance,
            currency: $request->currency
        );
    }

    public function rollin(Request $request)
    {
        $this->validateProviderRequest(request: $request->all(), rules: [
            'playerId' => 'required|string',
            'agentId' => 'required|int',
            'currency' => 'required|string',
            'amount' => 'required|numeric',
            'gameCode' => 'required|string',
            'gameRound' => 'required|string',
            'eventTime' => 'required|string'
        ]);

        $balance = $this->service->multiplayerSettle(request: $request);

        return $this->response->multiplayerTransactionResponse(
            balance: $balance,
            currency: $request->currency
        );
    }
}
