<?php

use Tests\TestCase;
use Providers\Hcg\HcgResponse;

class HcgResponseTest extends TestCase
{
    private function makeResponse()
    {
        return new HcgResponse;
    }

    public function test_casinoSuccess_stubData_expected()
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

    public function test_providerSuccess_stubData_expected()
    {
        $balance = 1000;

        $response = $this->makeResponse();
        $result = $response->providerSuccess(balance: $balance);

        $this->assertSame(
            expected: [
                'code' => 0,
                'gold' => $balance
            ],
            actual: $result->getData(true)
        );
    }

    public function test_gameOfflineNotification_stubData_expected()
    {
        $response = $this->makeResponse();
        $result = $response->gameOfflineNotification();

        $this->assertSame(expected: ['code' => 0], actual: $result->getData(true));
    }
}