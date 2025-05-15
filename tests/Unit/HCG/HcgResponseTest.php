<?php

use Tests\TestCase;
use App\GameProviders\V2\Hcg\HcgResponse;

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
}