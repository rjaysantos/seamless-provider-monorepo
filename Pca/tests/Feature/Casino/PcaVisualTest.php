<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class PcaVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_validRequest_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'bet_id' => 'testTransactionID',
            'wager_amount' => 100.00,
            'payout_amount' => 500.00,
            'ref_id' => 'testRefID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/reports/gameRoundStatus?*' => Http::response(json_encode([
                'code' => 200,
                'data' => [
                    'game_history_url' => ['testUrl.com']
                ],
            ]))
        ]);

        $response = $this->post('pca/in/visual', $request, ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/reports/gameRoundStatus' .
                '?game_round=testRefID&timezone=Asia%2FKuala_Lumpur' &&
                $request->hasHeader('x-auth-admin-key', '3bd7228891fb21391c355dda69a27548044e' .
                    'bf2bfc7d7c3e39c3f3a08e72e4e0') &&
                $request['game_round'] == 'testRefID' &&
                $request['timezone'] == 'Asia/Kuala_Lumpur';
        });
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequest_expectedData($visualParams)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ];

        unset($request[$visualParams]);

        $response = $this->post('pca/in/visual', $request, [
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

    public function test_visual_invalidCurrency_expectedData()
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'BRL'
        ];

        $response = $this->post('pca/in/visual', $request, [
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

    public function test_visual_invalidBearerToken_expectedData()
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('pca/in/visual', $request, [
            'Authorization' => 'Bearer ' . 'invalidBearerToken',
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
        DB::table('pca.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('pca/in/visual', $request, ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Player not found',
            'data' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_transactionNotFound_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'bet_id' => 'testTransactionID',
            'wager_amount' => 100.00,
            'payout_amount' => 500.00,
            'ref_id' => 'testRefID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'invalidTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post('pca/in/visual', $request, ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_thirdPartyApiError_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'bet_id' => 'testTransactionID',
            'wager_amount' => 100.00,
            'payout_amount' => 500.00,
            'ref_id' => 'testRefID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/reports/gameRoundStatus?*' => Http::response(json_encode([
                'code' => 500,
                'data' => null,
            ]))
        ]);

        $response = $this->post('pca/in/visual', $request, ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/reports/gameRoundStatus' .
                '?game_round=testRefID&timezone=Asia%2FKuala_Lumpur' &&
                $request->hasHeader('x-auth-admin-key', '3bd7228891fb21391c355dda69a27548044e' .
                    'bf2bfc7d7c3e39c3f3a08e72e4e0') &&
                $request['game_round'] == 'testRefID' &&
                $request['timezone'] == 'Asia/Kuala_Lumpur';
        });
    }
}
