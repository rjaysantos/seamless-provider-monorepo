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
}