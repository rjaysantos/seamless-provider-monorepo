<?php

use Tests\TestCase;
use Providers\Bes\BesResponse;

class BesResponseTest extends TestCase
{
    private function makeResponse(): BesResponse
    {
        return new BesResponse();
    }

    public function test_casinoSuccess_stubData_expectedData()
    {
        $data = 'testLaunchUrl.com';

        $response = $this->makeResponse();
        $result = $response->casinoResponse(data: $data);

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

    public function test_balance_stubResponse_expectedData()
    {
        $action = 1;
        $currency = 'IDR';
        $balance = 1000;

        $response = $this->makeResponse();
        $result = $response->balance(action: $action, currency: $currency, balance: $balance);

        $this->assertSame(
            expected: [
                'action' => $action,
                'status' => 1,
                'currency' => $currency,
                'balance' => $balance
            ],
            actual: $result->getData(true)
        );
    }
}