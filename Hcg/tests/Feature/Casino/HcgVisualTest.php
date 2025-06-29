<?php

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

class HcgVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hcg.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hcg.reports RESTART IDENTITY;');
    }

    public function test_visual_validRequest_expectedData()
    {
        DB::table('hcg.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hcg.reports')->insert([
            'ext_id' => 'wagerpayout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200.0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayIDu001',
            'bet_id' => 'wagerpayout-testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('hcg/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https://order.hcgame888.com/#/order_details/en/1560/' . 'testTransactionID',
            'error' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_validRequestFormattedExtID_expectedData()
    {
        DB::table('hcg.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hcg.reports')->insert([
            'ext_id' => 'wagerpayout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200.0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayIDu001',
            'bet_id' => 'wagerpayout-testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('hcg/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https://order.hcgame888.com/#/order_details/en/1560/testTransactionID',
            'error' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_invalidBearerToken_expectedData()
    {
        $request = [
            'play_id' => 'testPlayIDu001',
            'bet_id' => 'wagerpayout-testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('hcg/in/visual', $request, [
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
        DB::table('hcg.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'wagerpayout-testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('hcg/in/visual', $request, [
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
        DB::table('hcg.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hcg.reports')->insert([
            'ext_id' => 'wagerpayout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200.0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayIDu001',
            'bet_id' => 'invalidTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('hcg/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null,
        ]);
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequest_expectedData($param)
    {
        $request = [
            'play_id' => 'testPlayIDu001',
            'bet_id' => 'wagerpayout-testTransactionID',
            'currency' => 'IDR'
        ];

        unset($request[$param]);

        $response = $this->post('hcg/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'code' => 422,
            'data' => NULL,
            'error' => 'invalid request format',
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
}
