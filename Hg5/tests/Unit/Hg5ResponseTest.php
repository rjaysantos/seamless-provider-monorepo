<?php

use Carbon\Carbon;
use Tests\TestCase;
use Providers\Hg5\Hg5Response;

class Hg5ResponseTest extends TestCase
{
    private function makeResponse(): Hg5Response
    {
        return new Hg5Response();
    }

    public function test_casinoSuccess_stubData_expectedData()
    {
        $data = 'testUrl.com';

        $response = $this->makeResponse();
        $result = $response->casinoSuccess(data: $data);

        $this->assertSame(
            expected: [
                'success' => true,
                'code' => 200,
                'data' => $data,
                'error' => null
            ],
            actual: $result->getData(true)
        );
    }

    public function test_visualHtml_stubData_expectedData()
    {
        $data = [
            "playID" => "testPlayID",
            "currency" => "IDR",
            "trxID" => "testTransactionID",
            "roundData" => [
                0 => [
                    "roundID" => "testRound1",
                    "bet" => 100,
                    "win" => 100
                ],
                1 => [
                    "roundID" => "testRound2",
                    "bet" => 100,
                    "win" => 200
                ]
            ],
        ];

        $response = $this->makeResponse();
        $result = $response->visualHtml(data: $data);

        $this->assertEquals(expected: $data, actual: $result->getData());
    }

    public function test_balance_stubData_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $data = (object) [
            'balance' => 1000,
            'currency' => 'IDR'
        ];

        $response = $this->makeResponse();
        $result = $response->balance(data: $data);

        $this->assertSame(
            expected: [
                'data' => [
                    'balance' => $data->balance,
                    'currency' => $data->currency
                ],
                'status' => [
                    'code' => '0',
                    'message' => 'success',
                    'datetime' => '2024-01-01T00:00:00.000000000-04:00'
                ]
            ],
            actual: $result->getData(true)
        );
    }

    public function test_authenticate_stubData_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $data = (object) [
            'playID' => 'testPlayerID',
            'currency' => 'IDR',
            'sessionID' => 'testSessionID',
            'balance' => 1000
        ];

        $response = $this->makeResponse();
        $result = $response->authenticate(data: $data);

        $this->assertSame(
            expected: [
                'data' => [
                    'playerId' => $data->playID,
                    'currency' => $data->currency,
                    'sessionId' => $data->sessionID,
                    'balance' => $data->balance
                ],
                'status' => [
                    'code' => '0',
                    'message' => 'success',
                    'datetime' => '2024-01-01T00:00:00.000000000-04:00'
                ]
            ],
            actual: $result->getData(true)
        );
    }

    public function test_singleTransactionResponse_stubData_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $balance = 1000;
        $currency = 'IDR';
        $gameRound = 'testGameRound';

        $response = $this->makeResponse();
        $result = $response->singleTransactionResponse(
            balance: $balance,
            currency: $currency,
            gameRound: $gameRound
        );

        $this->assertSame(
            expected: [
                'data' => [
                    'balance' => $balance,
                    'currency' => $currency,
                    'gameRound' => $gameRound
                ],
                'status' => [
                    'code' => '0',
                    'message' => 'success',
                    'datetime' => '2024-01-01T00:00:00.000000000-04:00'
                ]
            ],
            actual: $result->getData(true)
        );
    }

    public function test_multipleTransactionResponse_stubData_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $data = [
            [
                'code' => '0',
                'message' => '',
                'balance' => 1200,
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ],
            [
                'code' => '0',
                'message' => '',
                'balance' => 1200,
                'currency' => 'IDR',
                'playerId' => 'testPlayID2',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $response = $this->makeResponse();
        $result = $response->multipleTransactionResponse(data: $data);

        $this->assertSame(
            expected: [
                'data' => $data,
                'status' => [
                    'code' => '0',
                    'message' => 'success',
                    'datetime' => '2024-01-01T00:00:00.000000000-04:00'
                ]
            ],
            actual: $result->getData(true)
        );
    }

    public function test_multiplayerTransactionResponse_stubData_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $balance = 1000;
        $currency = 'IDR';

        $response = $this->makeResponse();
        $result = $response->multiplayerTransactionResponse(balance: $balance, currency: $currency);

        $this->assertSame(
            expected: [
                'data' => [
                    'balance' => $balance,
                    'currency' => $currency
                ],
                'status' => [
                    'code' => '0',
                    'message' => 'success',
                    'datetime' => '2024-01-01T00:00:00.000000000-04:00'
                ]
            ],
            actual: $result->getData(true)
        );
    }
}
