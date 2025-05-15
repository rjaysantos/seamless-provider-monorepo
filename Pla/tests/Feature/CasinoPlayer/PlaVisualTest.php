<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;

class PlaVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pla.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pla.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_validRequest_expectedData()
    {
        DB::table('pla.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pla.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 500.00,
            'ref_id' => 'testRefID'
        ]);

        Http::fake([
            '/reports/gameRoundStatus?game_round=testTransactionID&timezone=Asia%2FKuala_Lumpur' => Http::response(json_encode([
                'code' => 200,
                'data' => [
                    'game_history_url' => 'testUrl.com'
                ],
            ]))
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ];

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl.com',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/reports/gameRoundStatus' .
                '?game_round=testTransactionID&timezone=Asia%2FKuala_Lumpur' &&
                $request->hasHeader('x-auth-admin-key', '5d4cf20f73ca4413060d41cf2733c64c7d7b93a03f7f4fdebd9c9a660f8a0dab') &&
                $request['game_round'] == 'testTransactionID' &&
                $request['timezone'] == 'Asia/Kuala_Lumpur';
        });
    }

    /**
     * @dataProvider visualParams
     */
    public function test_visual_invalidRequest_expectedData($visualParams)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ];

        unset($request[$visualParams]);

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'invalid request format'
        ]);
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
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ];

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . 'invalidBearerToken',
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);
    }

    public function test_visual_playerNotFound_expectedData()
    {
        DB::table('pla.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pla.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 500.00,
            'ref_id' => 'testRefID'
        ]);

        Http::fake([
            '/reports/gameRoundStatus?game_round=testTransactionID&timezone=Asia%2FKuala_Lumpur' => Http::response(json_encode([
                'code' => 200,
                'data' => [
                    'game_history_url' => 'testUrl.com'
                ],
            ]))
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ];

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Player not found',
            'data' => null
        ]);
    }

    public function test_visual_transactionNotFound_expectedData()
    {
        DB::table('pla.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pla.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 500.00,
            'ref_id' => 'testRefID'
        ]);

        Http::fake([
            '/reports/gameRoundStatus?game_round=testTransactionID&timezone=Asia%2FKuala_Lumpur' => Http::response(json_encode([
                'code' => 200,
                'data' => [
                    'game_history_url' => 'testUrl.com'
                ],
            ]))
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'invalidTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null
        ]);
    }

    public function test_visual_thirdPartyApiError_expectedData()
    {
        DB::table('pla.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pla.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 500.00,
            'ref_id' => 'testRefID'
        ]);

        Http::fake([
            '/reports/gameRoundStatus?game_round=testTransactionID&timezone=Asia%2FKuala_Lumpur' => Http::response(json_encode([
                'code' => 500,
                'data' => null,
            ]))
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ];

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/reports/gameRoundStatus' .
                '?game_round=testTransactionID&timezone=Asia%2FKuala_Lumpur' &&
                $request->hasHeader('x-auth-admin-key', '5d4cf20f73ca4413060d41cf2733c64c7d7b93a03f7f4fdebd9c9a660f8a0dab') &&
                $request['game_round'] == 'testTransactionID' &&
                $request['timezone'] == 'Asia/Kuala_Lumpur';
        });
    }
}