<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class YgrVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ygr.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_validRequest_expectedData()
    {
        DB::table('ygr.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
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

        Http::fake([
            '/GetGameDetailUrl' => Http::response(json_encode([
                'ErrorCode' => 0,
                'Data' => (object) [
                    'Url' => 'testVisual.com'
                ]
            ]))
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'payout-testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post('ygr/in/visual', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testVisual.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/GetGameDetailUrl' &&
                $request['WagersId'] == 'testTransactionID' &&
                $request['Lang'] == 'id-ID';
        });
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequest_expectedData($parameter)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'payout-testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        unset($request[$parameter]);

        $response = $this->post('ygr/in/visual', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
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
            'bet_id' => 'payout-testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post('ygr/in/visual', $request, [
            'Authorization' => 'Bearer ' . 'invalidTestToken'
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);

        $response->assertStatus(401);
    }

    public function test_visual_transactionNotFound_expectedData()
    {
        DB::table('ygr.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
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
            'play_id' => 'testPlayID',
            'bet_id' => 'invalidTransaction',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post('ygr/in/visual', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

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
        DB::table('ygr.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
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

        Http::fake([
            '/GetGameDetailUrl' => Http::response(json_encode([
                'ErrorCode' => 564651,
                'data' => []
            ]))
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'payout-testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ];

        $response = $this->post('ygr/in/visual', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/GetGameDetailUrl' &&
                $request['WagersId'] == 'testTransactionID' &&
                $request['Lang'] == 'id-ID';
        });
    }
}
