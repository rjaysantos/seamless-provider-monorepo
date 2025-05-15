<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class BesVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE bes.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE bes.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_validRequest_expectedData()
    {
        DB::table('bes.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('bes.reports')->insert([
            'trx_id' => 'testRoundID-testTransID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ];

        Http::fake([
            '/api/game/getdetailsurl' => Http::response(json_encode([
                'logurl' => 'testVisualUrl',
                'status' => 1
            ]))
        ]);

        $response = $this->post('bes/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testVisualUrl',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.stag-topgame.com/api/game/getdetailsurl' &&
                $request['cert'] == 'MCo9ktIXjOiGnhqlZVdy' &&
                $request['extension1'] == 'besoftaixswuat' &&
                $request['transId'] == 'testTransID' &&
                $request['lang'] == 'en';
        });
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequest_expectedData($param)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR'
        ];

        unset($request[$param]);

        $response = $this->post('bes/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

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
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR'
        ];

        $response = $this->post('bes/in/visual', $request, [
            'Authorization' => 'invalidBearerToken',
        ]);

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
        DB::table('bes.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR'
        ];

        $response = $this->post('bes/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Player not found',
            'data' => null,
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_transactionNotFound_expectedData()
    {
        DB::table('bes.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('bes.reports')->insert([
            'trx_id' => 'testRoundID-testTransID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'invalidTransactionID',
            'currency' => 'test-currency',
        ];

        $response = $this->post('bes/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null,
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_thirdPartyError_expectedData()
    {
        DB::table('bes.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('bes.reports')
            ->insert([
                'trx_id' => 'testRoundID-testTransID',
                'bet_amount' => 100,
                'win_amount' => 0,
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/api/game/getdetailsurl' => Http::response('', 500)
        ]);

        $response = $this->post('bes/in/visual', $request, [
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

    #[DataProvider('getDetailUrlParams')]
    public function test_visual_missingThirdPartyResponse_expectedData($param)
    {
        DB::table('bes.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('bes.reports')->insert([
            'trx_id' => 'testRoundID-testTransID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'test-currency',
        ];

        $apiResponse = [
            'logurl' => 'testVisualUrl',
            'status' => 1
        ];

        unset($apiResponse[$param]);

        Http::fake([
            '/api/game/getdetailsurl' => Http::response(json_encode($apiResponse))
        ]);

        $response = $this->post('bes/in/visual', $request, [
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

    #[DataProvider('getDetailUrlParams')]
    public function test_visual_invalidThirdPartyResponse_expectedData($param, $value)
    {
        DB::table('bes.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('bes.reports')->insert([
            'trx_id' => 'testRoundID-testTransID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'test-currency',
        ];

        $apiResponse = [
            'logurl' => 'testVisualUrl',
            'status' => 1
        ];

        $apiResponse[$param] = $value;

        Http::fake([
            '/api/game/getdetailsurl' => Http::response(json_encode($apiResponse))
        ]);

        $response = $this->post('/bes/in/visual', $request, [
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

    public static function getDetailUrlParams()
    {
        return [
            ['logurl', 123],
            ['status', 'test']
        ];
    }

    public function test_visual_thirdPartyResponseStatusNot1_expectedData()
    {
        DB::table('bes.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('bes.reports')->insert([
            'trx_id' => 'testRoundID-testTransID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'test-currency',
        ];

        Http::fake([
            '/api/game/getdetailsurl' => Http::response(json_encode([
                'status' => 999
            ]))
        ]);

        $response = $this->post('/bes/in/visual', $request, [
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
}
