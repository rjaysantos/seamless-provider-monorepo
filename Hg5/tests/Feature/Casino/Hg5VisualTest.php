<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class Hg5VisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_validRequest_expectedData()
    {
        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        Http::fake([
            '/GrandPriest/order/detail*' => Http::response(json_encode([
                'status' => [
                    'code' => '0',
                    'message' => 'testVisualUrl.com'
                ]
            ])),
            '/GrandPriest/orders*' => Http::response(json_encode([
                'data' => [
                    'list' => [
                        [
                            'gameroundid' => 'testRound1',
                            'round' => 'testTransactionID',
                            'win' => 100,
                            'bet' => 100
                        ]
                    ]
                ],
                'status' => [
                    'code' => '0',
                    'message' => 'success'
                ]
            ]))
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post(
            uri: 'hg5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]
        );

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testVisualUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/orders' .
                '?starttime=2021-01-01%2000%3A00%3A00&endtime=2021-01-01%2000%3A00%3A05&page=1&account=testPlayID' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['starttime'] == '2021-01-01 00:00:00' &&
                $request['endtime'] == '2021-01-01 00:00:05' &&
                $request['page'] == 1 &&
                $request['account'] == 'testPlayID';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/order/detail' .
                '?roundid=testTransactionID&account=testPlayID' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['roundid'] == 'testTransactionID' &&
                $request['account'] == 'testPlayID';
        });
    }

    public function test_visual_validRequestFishGame_expectedData()
    {
        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'hg5-testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        Http::fake([
            '/GrandPriest/orders*' => Http::response(json_encode([
                'data' => [
                    'list' => [
                        [
                            'gameroundid' => 'testTransactionID',
                            'round' => 'testRound1',
                            'win' => 100,
                            'bet' => 100
                        ],
                        [
                            'gameroundid' => 'testTransactionID',
                            'round' => 'testRound2',
                            'win' => 200,
                            'bet' => 100
                        ],
                    ]
                ],
                'status' => [
                    'code' => '0',
                    'message' => 'success'
                ]
            ]))
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'hg5-testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post(
            uri: 'hg5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]
        );

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertTrue(
            Str::contains(
                $response->getContent(),
                'visual'
            )
        );

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/orders' .
                '?starttime=2021-01-01%2000%3A00%3A00&endtime=2021-01-01%2000%3A00%3A05&page=1&account=testPlayID' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['starttime'] == '2021-01-01 00:00:00' &&
                $request['endtime'] == '2021-01-01 00:00:05' &&
                $request['page'] == 1 &&
                $request['account'] == 'testPlayID';
        });
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequest_expectedData($parameter)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        unset($request[$parameter]);

        $response = $this->post(
            uri: 'hg5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]
        );

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'invalid request format'
        ]);

        $response->assertStatus(200);
    }

    public static function visualParams()
    {
        return [
            ['play_id'],
            ['bet_id'],
            ['currency']
        ];
    }

    public function test_visual_invalidBearerToken_expectedData()
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post(
            uri: 'hg5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . 'invalidTestToken']
        );

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);

        $response->assertStatus(401);
    }

    public function test_visual_playerNotFound_expectedData()
    {
        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        $response = $this->post(
            uri: 'hg5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]
        );

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Player not found',
            'data' => null,
        ]);
    }

    public function test_visual_transactionNotFound_expectedData()
    {
        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'invalidTransaction',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post(
            uri: 'hg5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]
        );

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_orderQueryThirdPartyApiError_expectedData()
    {
        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        Http::fake([
            '/GrandPriest/orders*' => Http::response(json_encode([
                'status' => ['code' => '68132648312']
            ]))
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post(
            uri: 'hg5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]
        );

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/orders' .
                '?starttime=2021-01-01%2000%3A00%3A00&endtime=2021-01-01%2000%3A00%3A05&page=1&account=testPlayID' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['starttime'] == '2021-01-01 00:00:00' &&
                $request['endtime'] == '2021-01-01 00:00:05' &&
                $request['page'] == 1 &&
                $request['account'] == 'testPlayID';
        });
    }

    public function test_visual_orderDetailLinkThirdPartyApiError_expectedData()
    {
        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        Http::fake([
            '/GrandPriest/orders*' => Http::response(json_encode([
                'data' => [
                    'list' => [
                        [
                            'gameroundid' => 'testRound1',
                            'round' => 'testTransactionID',
                            'win' => 100,
                            'bet' => 100
                        ]
                    ]
                ],
                'status' => [
                    'code' => '0',
                    'message' => 'success'
                ]
            ])),
            '/GrandPriest/order/detail*' => Http::response(json_encode([
                'status' => ['code' => '534345354']
            ]))
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post(
            uri: 'hg5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]
        );

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/orders' .
                '?starttime=2021-01-01%2000%3A00%3A00&endtime=2021-01-01%2000%3A00%3A05&page=1&account=testPlayID' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['starttime'] == '2021-01-01 00:00:00' &&
                $request['endtime'] == '2021-01-01 00:00:05' &&
                $request['page'] == 1 &&
                $request['account'] == 'testPlayID';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/order/detail' .
                '?roundid=testTransactionID&account=testPlayID' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['roundid'] == 'testTransactionID' &&
                $request['account'] == 'testPlayID';
        });
    }
}