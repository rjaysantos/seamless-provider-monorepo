<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\GameProviders\V2\Ygr\YgrResponse;

class YgrResponseTest extends TestCase
{
    private function makeResponse(): YgrResponse
    {
        return new YgrResponse();
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

    // public function test_verifyToken_stubData_expectedData()
    // {
    //     Carbon::setTestNow('2021-01-01 00:00:00');

    //     $data = (object) [
    //         'ownerId' => 'testOwnerID',
    //         'parentId' => 'testParentID',
    //         'gameId' => 'testGameID',
    //         'userId' => 'testUserID',
    //         'nickname' => 'testNickname',
    //         'currency' => 'IDR',
    //         'amount' => 100.01
    //     ];

    //     $response = $this->makeResponse();
    //     $result = $response->verifyToken(data: $data);

    //     $this->assertSame(
    //         expected: [
    //             'data' => [
    //                 'ownerId' => 'testOwnerID',
    //                 'parentId' => 'testParentID',
    //                 'gameId' => 'testGameID',
    //                 'userId' => 'testUserID',
    //                 'nickname' => 'testNickname',
    //                 'currency' => 'IDR',
    //                 'amount' => 100.01
    //             ],
    //             'status' => [
    //                 'code' => '0',
    //                 'message' => 'Success',
    //                 'dateTime' => '2021-01-01T00:00:00+08:00'
    //                 // 'traceCode' => Str::uuid()->toString()
    //             ]
    //         ],
    //         actual: $result->getData(true)
    //     );

    //     Carbon::setTestNow();
    // }
}