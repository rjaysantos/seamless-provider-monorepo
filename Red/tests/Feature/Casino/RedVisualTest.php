<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class RedVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE red.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE red.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE red.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_validRequest_expectedData()
    {
        DB::table('red.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('red.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayID',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200.0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/bet/results' => Http::response([
                'status' => 1,
                'url' => 'testVisualUrl.com',
            ])
        ]);

        $response = $this->post('red/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testVisualUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://uat.ps9games.com' . '/bet/results' &&
                $request->hasHeader('ag-code', 'MPO0114') &&
                $request->hasHeader('ag-token', '3BQ9KGFtnQtno4kz12bMP4UqhVqWlWtz') &&
                $request['prd_id'] == 213 &&
                $request['txn_id'] == 'testTransactionID' &&
                $request['lang'] == 'en';
        });
    }

    #[DataProvider('betResultParams')]
    public function test_visual_invalidRequest_expectedData($param)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ];

        unset($request[$param]);

        $response = $this->post('red/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => "invalid request format",
            'data' => NULL
        ]);

        $response->assertStatus(200);
    }

    public static function betResultParams()
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
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('red/in/visual', $request, [
            'Authorization' => 'Bearer invalid token'
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => NULL
        ]);

        $response->assertStatus(401);
    }

    public function test_visual_playerNotFound_expectedData()
    {
        DB::table('red.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('red/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
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
        DB::table('red.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('red.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayID',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200.0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'payout-invalidTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('red/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
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
        DB::table('red.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('red.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayID',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200.0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/bet/results' => Http::response([
                'status' => 0,
                'error' => 'INVALID'
            ])
        ]);

        $response = $this->post('red/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('getBetResultResponseParams')]
    public function test_visual_thirdPartyInvalidResponseFormat_expectedData($parameter)
    {
        DB::table('red.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('red.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayID',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200.0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ];

        $apiResponse = [
            'status' => 1,
            'url' => 'testVisualUrl.com'
        ];

        unset($apiResponse[$parameter]);

        Http::fake(['/bet/results' => Http::response($apiResponse)]);

        $response = $this->post('red/in/visual', $request, [
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

    public static function getBetResultResponseParams()
    {
        return [
            ['status'],
            ['url']
        ];
    }
}
