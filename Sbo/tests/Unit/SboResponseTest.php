<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Sbo\SboResponse;
use Illuminate\Http\JsonResponse;

class SboResponseTest extends TestCase
{
    private function makeResponse()
    {
        return new SboResponse;
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