<?php

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

class OrsVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ors.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ors.reports RESTART IDENTITY;');
    }

    public function test_visual_validRequest_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        Http::fake([
            '/api/v2/platform/transaction/history*' => Http::response(json_encode([
                'rs_code' => 'S-100',
                'rs_message' => 'success',
                'records' => [
                    [
                        'result_url' => 'test-result-url1'
                    ]
                ]
            ]))
        ]);

        $response = $this->post('ors/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-result-url1',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://xyz.pwqr820.com:9003/api/v2/platform/transaction/history?transaction_id=testTransactionID&player_id=testPlayID&game_type_id=2' &&
                $request->hasHeader('key', 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x') &&
                $request->hasHeader('operator-name', 'mog052testidrslot') &&
                $request['transaction_id'] == 'testTransactionID' &&
                $request['player_id'] == 'testPlayID' &&
                $request['game_type_id'] == 2;
        });
    }

    public function test_visual_invalidBearerToken_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        $response = $this->post('ors/in/visual', $request, [
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
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        Http::fake([
            '/api/v2/platform/transaction/history*' => Http::response(json_encode([
                'rs_code' => 'S-100',
                'rs_message' => 'success',
                'records' => [
                    [
                        'result_url' => 'test-result-url1'
                    ]
                ]
            ]))
        ]);

        $response = $this->post('ors/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Player not found',
            'data' => null,
        ]);
    }

    public function test_visual_transactionNotFound_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'InvalidTransactionID',
            'currency' => 'IDR',
        ];

        Http::fake([
            '/api/v2/platform/transaction/history*' => Http::response(json_encode([
                'rs_code' => 'S-100',
                'rs_message' => 'success',
                'records' => [
                    [
                        'result_url' => 'test-result-url1'
                    ]
                ]
            ]))
        ]);

        $response = $this->post('ors/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null,
        ]);
    }

    public function test_visual_thirdPartyApiError_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        Http::fake([
            '/api/v2/platform/transaction/history*' => Http::response(json_encode([
                'rs_code' => 'S-200',
                'records' => [
                    [
                        'result_url' => 'failed'
                    ]
                ]
            ]))
        ]);

        $response = $this->post('ors/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequest_expectedData($param)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransacID',
            'currency' => 'IDR',
        ];

        unset($request[$param]);

        $response = $this->post('ors/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'code' => 422,
            'data' => NULL,
            'error' => "invalid request format",
        ]);

        $response->assertStatus(200);
    }

    public static function visualParams()
    {
        return [
            ['play_id'],
            ['bet_id'],
            ['currency'],
        ];
    }
}
