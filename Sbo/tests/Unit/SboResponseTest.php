<?php

use Tests\TestCase;
use Providers\Sbo\SboResponse;

class SboResponseTest extends TestCase
{
    private function makeResponse(): SboResponse
    {
        return new SboResponse();
    }

    public function test_balance_stubData_expectedData()
    {
        $playID = 'testPlayID';
        $balance = 2200;

        $response = $this->makeResponse();
        $result = $response->balance(playID: $playID, balance: $balance);

        $this->assertSame(
            expected: [
                'AccountName' => $playID,
                'Balance' => $balance,
                'ErrorCode' => 0,
                'ErrorMessage' => 'No Error'
            ],
            actual: $result->getData(true)
        );
    }
}
