<?php

use Tests\TestCase;
use App\GameProviders\V2\Jdb\JdbResponse;

class JdbResponseTest extends TestCase
{
    private function makeResponse(): JdbResponse
    {
        return new JdbResponse();
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
        $balance = 1000;

        $response = $this->makeResponse();
        $result = $response->providerSuccess(balance: $balance);

        $this->assertSame(
            expected: [
                'status' => '0000',
                'balance' => $balance,
                'err_text' => ''
            ],
            actual: $result->getData(true)
        );
    }
}