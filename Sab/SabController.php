<?php

namespace Providers\Sab;

use Illuminate\Http\Request;
use Providers\Sab\SabService;
use Providers\Sab\SabResponse;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Sab\Exceptions\InvalidProviderRequestException;

class SabController
{
    public function __construct(
        private SabService $service,
        private SabResponse $response
    ) {}

    private function validateCasinoRequest(Request $request, array $rules): void
    {
        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails())
            throw new InvalidCasinoRequestException;
    }

    public function play(Request $request)
    {
        $this->validateCasinoRequest(
            request: $request,
            rules: [
                'playId' => 'required|string',
                'username' => 'required|string',
                'currency' => 'required|string|in:IDR,PHP,THB,VND,BRL,USD',
                'language' => 'required|string',
                'device' => 'required|integer'
            ]
        );

        $gameUrl = $this->service->getLaunchUrl(request: $request);

        return $this->response->casinoResponse(data: $gameUrl);
    }

    public function visual(Request $request)
    {
        $this->validateCasinoRequest(
            request: $request,
            rules: [
                'play_id' => 'required|string',
                'bet_id' => 'required|string'
            ]
        );

        $visualUrl = $this->service->getBetDetailUrl(request: $request);

        return $this->response->casinoResponse(data: $visualUrl);
    }

    public function visualHtml(string $encryptedTrxID)
    {
        $betDetailData = $this->service->getBetDetailData(encryptedTrxID: $encryptedTrxID);

        return $this->response->visualHtml(sportsbookDetails: $betDetailData);
    }

    private function validateProviderRequest(Request $request, array $rules): void
    {
        $validate = Validator::make($request->all(), $rules);

        if ($validate->fails())
            throw new InvalidProviderRequestException;
    }

    public function balance(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'key' => 'required|string',
                'message' => 'required|array',
                'message.userId' => 'required|string'
            ]
        );

        $balance = $this->service->getBalance(request: $request);

        return $this->response->balance(
            userID: $request->message['userId'],
            balance: $balance
        );
    }

    public function placeBet(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'key' => 'required|string',
                'message' => 'required|array',
                'message.operationId' => 'required|string',
                'message.refId' => 'required|string',
                'message.userId' => 'required|string',
                'message.betTime' => 'required|string',
                'message.betType' => 'required|integer',
                'message.actualAmount' => 'required|regex:/^\d+(\.\d{1,6})?$/',
                'message.IP' => 'required|string',
            ]
        );

        $this->service->placeBet(request: $request);

        return $this->response->placeBet(refID: $request->message['refId']);
    }

    public function confirmBet(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'key' => 'required|string',
                'message' => 'required|array',
                'message.operationId' => 'required|string',
                'message.userId' => 'required|string',
                'message.updateTime' => 'required|string',
                'message.txns' => 'required|array',
                'message.txns.*.refId' => 'required|string',
                'message.txns.*.txId' => 'required|integer',
                'message.txns.*.actualAmount' => 'required|regex:/^\d+(\.\d{1,6})?$/',
            ]
        );

        $balance = $this->service->confirmBet(request: $request);

        return $this->response->successWithBalance(balance: $balance);
    }

    public function cancelBet(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'key' => 'required|string',
                'message' => 'required|array',
                'message.operationId' => 'required|string',
                'message.userId' => 'required|string',
                'message.updateTime' => 'required|string',
                'message.txns' => 'required|array',
                'message.txns.*.refId' => 'required|string',
            ]
        );

        $balance = $this->service->cancelBet(request: $request);

        return $this->response->successWithBalance(balance: $balance);
    }

    public function outstanding(Request $request)
    {
        $this->validateCasinoRequest($request, [
            'currency' => 'required|string',
            'branchId' => 'required|integer',
            'start' => 'required|integer',
            'length' => 'required|integer'
        ]);

        $records = $this->service->getRunningTransactions(request: $request);

        return $this->response->outstanding(runningTransactions: $records);
    }

    public function placeBetParlay(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'key' => 'required|string',
                'message' => 'required|array',
                'message.operationId' => 'required|string',
                'message.userId' => 'required|string',
                'message.betTime' => 'required|string',
                'message.totalBetAmount' => 'required|regex:/^\d+(\.\d{1,6})?$/',
                'message.IP' => 'required|string',
                'message.txns' => 'required|array',
                'message.txns.*.refId' => 'required|string',
                'message.txns.*.betAmount' => 'required|regex:/^\d+(\.\d{1,6})?$/',
            ]
        );

        $this->service->placeBetParlay(request: $request);

        return $this->response->placeBetParlay(transactions: $request->message['txns']);
    }

    public function unsettle(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'key' => 'required|string',
                'message' => 'required|array',
                'message.operationId' => 'required|string',
                'message.txns' => 'required|array',
                'message.txns.*.userId' => 'required|string',
                'message.txns.*.txId' => 'required|integer',
                'message.txns.*.updateTime' => 'required|string',
            ]
        );

        $this->service->unsettle(request: $request);

        return $this->response->successWithoutBalance();
    }

    public function resettle(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'key' => 'required|string',
                'message' => 'required|array',
                'message.operationId' => 'required|string',
                'message.txns' => 'required|array',
                'message.txns.*.userId' => 'required|string',
                'message.txns.*.updateTime' => 'required|string',
                'message.txns.*.payout' => 'required|regex:/^\d+(\.\d{1,6})?$/',
                'message.txns.*.txId' => 'required|integer',
                'message.txns.*.status' => 'required|string'
            ]
        );

        $this->service->resettle(request: $request);

        return $this->response->successWithoutBalance();
    }

    public function adjustBalance(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'key' => 'required|string',
                'message' => 'required|array',
                'message.operationId' => 'required|string',
                'message.userId' => 'required|string',
                'message.txId' => 'required|integer',
                'message.time' => 'required|string',
                'message.betType' => 'required|integer',
                'message.balanceInfo' => 'required|array',
                'message.balanceInfo.creditAmount' => 'required|regex:/^\d+(\.\d{1,6})?$/',
                'message.balanceInfo.debitAmount' => 'required|regex:/^\d+(\.\d{1,6})?$/'
            ]
        );

        $this->service->adjustBalance(request: $request);

        return $this->response->successWithoutBalance();
    }

    public function settle(Request $request)
    {
        $this->validateProviderRequest(
            request: $request,
            rules: [
                'key' => 'required|string',
                'message' => 'required|array',
                'message.operationId' => 'required|string',
                'message.txns' => 'required|array',
                'message.txns.*.userId' => 'required|string',
                'message.txns.*.txId' => 'required|integer',
                'message.txns.*.payout' => 'required|regex:/^\d+(\.\d{1,6})?$/',
                'message.txns.*.updateTime' => 'required|string'
            ]
        );

        $this->service->settle(request: $request);

        return $this->response->successWithoutBalance();
    }
}
