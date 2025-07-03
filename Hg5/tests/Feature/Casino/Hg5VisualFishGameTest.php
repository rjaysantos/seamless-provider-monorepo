<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class Hg5VisualFishGameTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visualFishGame_validRequest_expectedData()
    {
        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'payout-hg5-testTransactionID',
            'round_id' => 'hg5-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 100,
            'bet_winlose' => 200,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00'
        ]);

        Http::fake([
            '/GrandPriest/order/detail*' => Http::response(json_encode([
                'status' => [
                    'code' => '0',
                    'message' => 'testVisualUrl.com'
                ]
            ])),
        ]);

        $request = [
            'trxID' => 'hg5-testTransactionID',
            'playID' => 'testPlayIDu001'
        ];

        $response = $this->get(uri: 'hg5/in/visual/fishgame/?' . http_build_query($request));

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testVisualUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/order/detail' .
                '?roundid=testTransactionID&account=testPlayIDu001' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['roundid'] == 'testTransactionID' &&
                $request['account'] == 'testPlayIDu001';
        });
    }

    #[DataProvider('visualFishGameParams')]
    public function test_visualFishGame_invalidRequest_expectedData($parameter)
    {
        $request = [
            'trxID' => 'hg5-testTransactionID',
            'playID' => 'testPlayIDu001'
        ];

        unset($request[$parameter]);

        $response = $this->get(uri: 'hg5/in/visual/fishgame/?' . http_build_query($request));

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'invalid request format'
        ]);

        $response->assertStatus(200);
    }

    public static function visualFishGameParams()
    {
        return [
            ['trxID'],
            ['playID'],
        ];
    }

    public function test_visualFishGame_thirdPartyAPIError_expectedData()
    {
        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'payout-hg5-testTransactionID',
            'round_id' => 'hg5-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 100,
            'bet_winlose' => 200,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00'
        ]);

        Http::fake([
            '/GrandPriest/order/detail*' => Http::response(json_encode([
                'status' => ['code' => '468513153']
            ])),
        ]);

        $request = [
            'trxID' => 'hg5-testTransactionID',
            'playID' => 'testPlayIDu001'
        ];

        $response = $this->get(uri: 'hg5/in/visual/fishgame/?' . http_build_query($request));

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/order/detail' .
                '?roundid=testTransactionID&account=testPlayIDu001' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['roundid'] == 'testTransactionID' &&
                $request['account'] == 'testPlayIDu001';
        });
    }
}
