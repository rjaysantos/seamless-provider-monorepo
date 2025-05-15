<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;

class Hg5VisualHtmlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visualHtml_validEncryptedPlayIDTrxID_expectedData()
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

        $playID = Crypt::encryptString('testPlayID');
        $trxID = Crypt::encryptString('hg5-testTransactionID');

        $response = $this->get("/hg5/in/visual/{$playID}/{$trxID}");

        $response->assertStatus(200);
        $response->assertViewIs('/var/www/html/providers/Hg5/views/hg5_visual.blade.php');

        $response->assertViewHas('playID', 'testPlayID');
        $response->assertViewHas('currency', 'IDR');
        $response->assertViewHas('trxID', 'hg5-testTransactionID');

        $responseData = $response->viewData('roundData');
        $this->assertEquals([
            ['roundID' => 'testRound1', 'bet' => 100, 'win' => 100],
            ['roundID' => 'testRound2', 'bet' => 100, 'win' => 200],
        ], $responseData);

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

    public function test_visualHtml_playerNotFound_expectedData()
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

        $playID = Crypt::encryptString('invalidPlayID');
        $trxID = Crypt::encryptString('hg5-testTransactionID');

        $response = $this->get("/hg5/in/visual/{$playID}/{$trxID}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Player not found',
            'data' => null
        ]);
    }

    public function test_visualHtml_transactionNotFound_expectedData()
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

        $playID = Crypt::encryptString('testPlayID');
        $trxID = Crypt::encryptString('invalidTransactionID');

        $response = $this->get("/hg5/in/visual/{$playID}/{$trxID}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null,
        ]);
    }

    public function test_visualHtml_thirdPartyApiError_expectedData()
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
                    'list' => []
                ],
                'status' => [
                    'code' => '0',
                    'message' => 'success'
                ]
            ]))
        ]);

        $playID = Crypt::encryptString('testPlayID');
        $trxID = Crypt::encryptString('hg5-testTransactionID');

        $response = $this->get("/hg5/in/visual/{$playID}/{$trxID}");

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

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
}