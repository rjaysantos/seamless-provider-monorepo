<?php

use Providers\Gs5\Gs5Response;
use Tests\TestCase;

class Gs5ResponseTest extends TestCase
{
    private function makeResponse(): Gs5Response
    {
        return new Gs5Response();
    }

    public function test_successTransaction_stubData_expectedData()
    {
        $balance = 1000;

        $response = $this->makeResponse();
        $result = $response->success(balance: $balance);

        $this->assertSame(
            expected: [
                'status_code' => 0,
                'balance' => $balance
            ],
            actual: $result->getData(true)
        );
    }

    public function test_authenticate_stubData_expectedData()
    {
        $data = (object) [
            'member_id' => 'testPlayID',
            'member_name' => 'testUsername',
            'balance' => 1000
        ];

        $response = $this->makeResponse();
        $result = $response->authenticate(data: $data);

        $this->assertEquals(
            expected: [
                'status_code' => 0,
                'member_id' => $data->member_id,
                'member_name' => $data->member_name,
                'balance' => $data->balance
            ],
            actual: $result->getData(true)
        );
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
}
