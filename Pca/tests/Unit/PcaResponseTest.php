<?php

use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Providers\Pca\PcaResponse;
use PHPUnit\Framework\Attributes\DataProvider;

class PcaResponseTest extends TestCase
{
    private function makeResponse(): PcaResponse
    {
        return new PcaResponse();
    }

    public function test_casinoSuccess_stubData_expectedData()
    {
        $data = 'testUrl.com';

        $response = $this->makeResponse();
        $result = $response->casinoSuccess(data: $data);

        $this->assertSame(expected: [
            'success' => true,
            'code' => 200,
            'data' => $data,
            'error' => null
        ], actual: $result->getData(true));
    }

    public function test_authenticate_stubDataSTG_expected()
    {
        $requestId = 'TEST_requestToken';
        $playID = 'TEST_PLAYERID';

        $expected = response()->json([
            "requestId" => $requestId,
            "username" => $playID,
            "currencyCode" => 'CNY',
            "countryCode" => 'CN'
        ]);

        $response = $this->makeResponse();
        $result = $response->authenticate(requestId: $requestId, playID: $playID, currency: 'IDR');

        $this->assertEquals(expected: $expected, actual: $result);
    }

    #[DataProvider('currencyAndCountryCode')]
    public function test_authenticate_stubDataPROD_expected($currency, $countryCode)
    {
        config(['app.env' => 'PRODUCTION']);

        $requestId = 'TEST_requestToken';
        $playID = 'TEST_TESTPLAYID';

        $expected = response()->json([
            "requestId" => $requestId,
            "username" => $playID,
            "currencyCode" => $currency,
            "countryCode" => $countryCode
        ]);

        $response = $this->makeResponse();
        $result = $response->authenticate(requestId: $requestId, playID: $playID, currency: $currency);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public static function currencyAndCountryCode()
    {
        return [
            ['IDR', 'ID'],
            ['PHP', 'PH'],
            ['VND', 'VN'],
            ['USD', 'US'],
            ['THB', 'TH'],
            ['MYR', 'MY'],
        ];
    }

    public function test_getBalance_stubData_expected()
    {
        $requestId = 'TEST_requestToken';
        $balance = 0;

        Carbon::setTestNow('2024-04-07 00:00:00');

        $expected = response()->json([
            "requestId" => $requestId,
            "balance" => [
                "real" => "0.00",
                "timestamp" => '2024-04-06 16:00:00.000'
            ]
        ]);

        $response = $this->makeResponse();
        $result = $response->getBalance(requestId: $requestId, balance: $balance);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    #[DataProvider('formattedBalance')]
    public function test_getBalance_balanceWithoutDecimalPoint_expected($balance, $expectedBalance)
    {
        $requestId = 'TEST_requestToken';

        Carbon::setTestNow('2024-04-07 00:00:00');

        $expected = response()->json([
            "requestId" => $requestId,
            "balance" => [
                "real" => $expectedBalance,
                "timestamp" => '2024-04-06 16:00:00.000'
            ]
        ]);

        $response = $this->makeResponse();
        $result = $response->getBalance(requestId: $requestId, balance: $balance);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_healthCheck_stubData_expected()
    {
        $expected = response()->json([]);

        $response = $this->makeResponse();
        $result = $response->healthCheck();

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_logout_stubData_expected()
    {
        $requestId = 'TEST_requestToken';

        $expected = response()->json(["requestId" => $requestId]);

        $response = $this->makeResponse();
        $result = $response->logout($requestId);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_bet_stubData_expected()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_invalidPlayer',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [
            ],
            'gameCodeName' => 'testGameID'
        ]);

        $balance = 0;

        $expected = response()->json([
            "requestId" => $request->requestId,
            "externalTransactionCode" => $request->transactionCode,
            "externalTransactionDate" => $request->transactionDate,
            "balance" => [
                "real" => "0.00",
                "timestamp" => $request->transactionDate
            ]
        ]);

        $response = $this->makeResponse();
        $result = $response->bet(request: $request, balance: $balance);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    #[DataProvider('formattedBalance')]
    public function test_bet_balanceWithoutDecimalPoint_expected($balance, $expectedBalance)
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_invalidPlayer',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [
            ],
            'gameCodeName' => 'testGameID'
        ]);

        $expected = response()->json([
            "requestId" => $request->requestId,
            "externalTransactionCode" => $request->transactionCode,
            "externalTransactionDate" => $request->transactionDate,
            "balance" => [
                "real" => $expectedBalance,
                "timestamp" => $request->transactionDate
            ]
        ]);

        $response = $this->makeResponse();
        $result = $response->bet(request: $request, balance: $balance);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_gameRoundResult_stubDataNoWin_expected()
    {
        $request = new Request([
            'requestId' => Str::random(150),
            'username' => 'test_playerID',
            'externalToken' => 'test_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testCodeName'
        ]);

        $balance = "10.00";

        Carbon::setTestNow('2024-01-01 00:00:00');

        $expected = response()->json([
            "requestId" => $request->requestId,
            'externalTransactionCode' => Str::substr($request->requestId, 0, 128),
            'externalTransactionDate' => '2023-12-31 16:00:00.000',
            "balance" => [
                "real" => $balance,
                "timestamp" => '2023-12-31 16:00:00.000'
            ]
        ]);

        $response = $this->makeResponse();
        $result = $response->gameRoundResult(request: $request, balance: $balance);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_gameRoundResult_stubDataWithWin_expected()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testCodeName'
        ]);

        $balance = "10.00";

        $expected = response()->json([
            "requestId" => $request->requestId,
            'externalTransactionCode' => $request->pay['transactionCode'],
            'externalTransactionDate' => $request->pay['transactionDate'],
            "balance" => [
                "real" => $balance,
                "timestamp" => $request->pay['transactionDate']
            ]
        ]);

        $response = $this->makeResponse();
        $result = $response->gameRoundResult(request: $request, balance: $balance);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    #[DataProvider('formattedBalance')]
    public function test_gameRoundResult_balanceWithoutDecimalPoint_expected($balance, $expectedBalance)
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testCodeName'
        ]);

        Carbon::setTestNow('2024-04-07 00:00:00');

        $expected = response()->json([
            "requestId" => $request->requestId,
            'externalTransactionCode' => $request->pay['transactionCode'],
            'externalTransactionDate' => $request->pay['transactionDate'],
            "balance" => [
                "real" => $expectedBalance,
                "timestamp" => $request->pay['transactionDate']
            ]
        ]);

        $response = $this->makeResponse();
        $result = $response->gameRoundResult(request: $request, balance: $balance);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public static function formattedBalance()
    {
        return [
            [123, '123.00'],
            [123.456789, '123.45'],
            [123.409987, '123.40'],
            [123.000, '123.00'],
            [123.000009, '123.00'],
            [100.000, '100.00'],
            [100, '100.00'],
        ];
    }
}