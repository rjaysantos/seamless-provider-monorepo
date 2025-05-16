<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class JdbVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE jdb.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE jdb.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE jdb.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_validRequestStaging_expectedData()
    {
        Carbon::setTestNow('2024-08-20 10:00:00');

        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('jdb.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 12:00:00',
            'created_at' => '2021-01-01 00:00:00',
            'history_id' => 'testHistoryID'
        ]);

        $payload = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ];

        Http::fake([
            '/apiRequest.do' => Http::response([
                'status' => '0000',
                'data' => [
                    [
                        'path' => 'test_visual_url'
                    ]
                ],
            ])
        ]);

        $response = $this->post('jdb/in/visual', $payload, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_visual_url',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://api.jdb711.com' . '/apiRequest.do' &&
                $request['dc'] == 'COLS' &&
                $request['x'] == '1UsP3XuqUP9OuEoCIDB_at6xp3FI1Uxmyb90RE7crk3Q_WUmZsPifR-2VY4zXOo9eo-QiF778aDXhQVHnKRjyy7z6k5hemQ3CR_GGz7PiRzGRaBEXgAhbj8o-Nsg2BZb72eeXItsdthEnLJZzcff0etpUFp_fVcF-KwPefP5Ox0';
        });
        Carbon::setTestNow();
    }

    public function test_visual_validRequestArcadeStaging_expectedData()
    {
        Carbon::setTestNow('2024-08-20 10:00:00');

        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('jdb.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 12:00:00',
            'created_at' => '2021-01-01 00:00:00',
            'history_id' => 'testHistoryID'
        ]);

        $payload = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '23-12332',
        ];

        Http::fake([
            '/apiRequest.do' => Http::response([
                'status' => '0000',
                'data' => [
                    [
                        'path' => 'test_visual_url'
                    ]
                ],
            ])
        ]);

        $response = $this->post('jdb/in/visual', $payload, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_visual_url',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://api.jdb711.com' . '/apiRequest.do' &&
                $request['dc'] == 'COLS' &&
                $request['x'] == '1UsP3XuqUP9OuEoCIDB_at6xp3FI1Uxmyb90RE7crk3Q_WUmZsPifR-2VY4zXOo9eo-QiF778aDXhQVHnKRjyy7z6k5hemQ3CR_GGz7PiRyHc-q4eVXaHWUiNZOjpWIY0mYFCLw9sjLVrcmGfbZ2DmLSiAq0AhCnESqAViIp0Hc';
        });
        Carbon::setTestNow();
    }

    public function test_records_invalidBearerToken_expected()
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransacID',
            'currency' => 'IDR',
            'game_id' => '12332',
        ];

        $response = $this->post('jdb/in/visual', $request, [
            'Authorization' => 'Bearer ' . 'Invalid Bearer Token',
        ]);

        $response->assertJson([
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => NULL,
        ]);

        $response->assertStatus(401);
    }

    public function test_visual_playerNotFound_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'invalidPlayer',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332',
        ];

        $response = $this->post('jdb/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Player not found',
            'data' => NULL,
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_transactionNotFoundRequest_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('jdb.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 12:00:00',
            'created_at' => '2021-01-01 00:00:00',
            'history_id' => 'testHistoryID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'invalidTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332',
        ];

        $response = $this->post('jdb/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => NULL,
        ]);
    }

    public function test_visual_thirdPartyInvalidResponse_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('jdb.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 12:00:00',
            'created_at' => '2021-01-01 00:00:00',
            'history_id' => 'testHistoryID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332',
        ];

        Http::fake([
            '/apiRequest.do' => Http::response([
                'status' => '9999',
                'data' => [
                    [
                        'path' => 'invalid'
                    ]
                ],
            ])
        ]);

        $response = $this->post('jdb/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('betResultParams')]
    public function test_visual_missingParameterRequest_expectedData($param)
    {
        $request = [
            'play_id' => 'rj2wgdrnzu027',
            'bet_id' => '7604333-11634',
            'currency' => 'IDR',
            'game_id' => '12332',
        ];

        unset($request[$param]);

        Http::fake([
            '/apiRequest.do' => Http::response([
                'status' => '0000',
                'data' => [
                    [
                        'path' => 'test_visual_url'
                    ]
                ],
            ])
        ]);

        $response = $this->post('jdb/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'invalid request format',
            'data' => NULL,
        ]);

        $response->assertStatus(200);
    }

    public static function betResultParams()
    {
        return [
            ['play_id'],
            ['bet_id'],
            ['currency'],
            ['game_id'],
        ];
    }
}
