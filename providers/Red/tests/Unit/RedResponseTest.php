<?php

use Tests\TestCase;
use Providers\Red\RedResponse;

class RedResponseTest extends TestCase
{
    private function makeResponse(): RedResponse
    {
        return new RedResponse();
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

    public function test_providerSuccess_stubData_expectedData()
    {
        $balance = 1000.12;

        $response = $this->makeResponse();
        $result = $response->providerSuccess(balance: $balance);

        $this->assertSame(
            expected: [
                'status' => 1,
                'balance' => $balance
            ],
            actual: $result->getData(true)
        );
    }
}
