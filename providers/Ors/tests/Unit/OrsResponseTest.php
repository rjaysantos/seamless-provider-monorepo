<?php

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Ors\OrsResponse;

class OrsResponseTest extends TestCase
{
    private function makeResponse(): OrsResponse
    {
        return new OrsResponse();
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

    public function test_authenticate_stubData_expectedData()
    {
        $playerStatus = 'activate';
        $token = 'testToken';

        $response = $this->makeResponse();
        $result = $response->authenticate(token: $token);

        $this->assertSame(
            expected: [
                'rs_code' => 'S-100',
                'rs_message' => 'success',
                'player_status' => $playerStatus,
                'token' => $token
            ],
            actual: $result->getData(true)
        );
    }

    public function test_getBalance_stubData_expectedData()
    {
        $playID = 'testPlayID';
        $balance = 100;
        $playerStatus = 'activate';
        $currency = 'IDR';

        Carbon::setTestNow('2025-01-01 00:00:00');

        $response = $this->makeResponse();
        $result = $response->getBalance(playID: $playID, balance: $balance, currency: $currency);

        $this->assertSame(
            expected: [
                'rs_code' => 'S-100',
                'rs_message' => 'success',
                'player_id' => $playID,
                'player_status' => $playerStatus,
                'balance' => $balance,
                'timestamp' => 1735660800,
                'currency' => $currency
            ],
            actual: $result->getData(true)
        );

        Carbon::setTestNow();
    }

    public function test_debit_stubData_expectedData()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'total_amount' => 250,
            'transaction_type' => 'debit',
            'game_id' => 123,
            'round_id' => 'testRoundID',
            'called_at' => 1234567891,
            'records' => [
                [
                    'transaction_id' => 'test_transacID_1',
                    'amount' => 150
                ]
            ],
            'signature' => 'testSignature'
        ]);

        $balance = 100;

        Carbon::setTestNow('2025-01-01 00:00:00');

        $response = $this->makeResponse();
        $result = $response->debit(request: $request, balance: $balance);

        $this->assertSame(
            expected: [
                'rs_code' => 'S-100',
                'rs_message' => 'success',
                'player_id' => $request->player_id,
                'total_amount' => $request->total_amount,
                'updated_balance' => $balance,
                'billing_at' => $request->called_at,
                'records' => $request->records,
            ],
            actual: $result->getData(true)
        );

        Carbon::setTestNow();
    }

    public function test_payout_stubData_expectedData()
    {
        $request = new Request([
            "player_id" => "testPlayerID",
            "amount" => 30,
            "transaction_id" => "testTransactionID",
            "called_at" => 123456789,
            "signature" => "testSignature"
        ]);

        $balance = 100;

        $response = $this->makeResponse();
        $result = $response->payout(request: $request, balance: $balance);

        $this->assertSame(
            expected: [
                'rs_code' => 'S-100',
                'rs_message' => 'success',
                'player_id' => $request->player_id,
                'amount' => $request->amount,
                'transaction_id' => $request->transaction_id,
                'updated_balance' => $balance,
                'billing_at' => $request->called_at
            ],
            actual: $result->getData(true)
        );
    }
}
