<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Sbo\SboResponse;

class SboResponseTest extends TestCase
{
    private function makeResponse(): SboResponse
    {
        return new SboResponse();
    }

    public function test_cancel_stubData_expectedData()
    {
        $request = new Request(['Username' => 'sbo_testPlayIDu027']);

        $response = $this->makeResponse();
        $result = $response->cancel(request: $request, balance: 1000.0);

        $this->assertSame(
            expected: [
                'AccountName' => $request->Username,
                'Balance' => 1000,
                'ErrorCode' => 0,
                'ErrorMessage' => 'No Error'
            ],
            actual: $result->getData(true)
        );
    }

    public function test_deduct_stubResponse_expected()
    {
        $request = new Request([
            'Username' => 'testUsername',
            'Amount' => 100.0
        ]);
        
        $balance = 1000.0;

        $expected = new JsonResponse([
            'AccountName' => 'testUsername',
            'Balance' => $balance,
            'BetAmount' => 100.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response = $this->makeResponse();
        $result = $response->deduct(request: $request, balance: $balance);

        $this->assertEquals(expected: $expected, actual: $result);
    }
}