<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use Providers\Gs5\Credentials\Gs5Staging;
use PHPUnit\Framework\Attributes\DataProvider;

class Gs5VisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE gs5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE gs5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_validRequest_expectedData()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('gs5.reports')->insert([
            'ext_id' => 'testTransactionID',
            'round_id' => 'testTransactionID',
            'web_id' => 1,
            'game_code' => 'testGameCode',
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'bet_winlose' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ];

        $credentials = new Gs5Staging;
        $expectedLaunchUrl = $credentials->getApiUrl() . '/Resource/game_history?' . http_build_query([
            'token' => $credentials->getToken(),
            'sn' => 'testTransactionID'
        ]);

        $response = $this->post(
            uri: 'gs5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . config('app.bearer')]
        );

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => $expectedLaunchUrl,
            'error' => null
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequest_expectedData($parameter)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ];

        unset($request[$parameter]);

        $response = $this->post(
            uri: 'gs5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . config('app.bearer')]
        );

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
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post(
            uri: 'gs5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer invalidToken']
        );

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
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post(
            uri: 'gs5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . config('app.bearer')]
        );

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
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('gs5.reports')->insert([
            'ext_id' => 'testTransactionID',
            'round_id' => 'testTransactionID',
            'web_id' => 1,
            'game_code' => 'testGameCode',
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'bet_winlose' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'invalidTransactionID',
            'currency' => 'IDR'
        ];

        $response = $this->post(
            uri: 'gs5/in/visual',
            data: $request,
            headers: ['Authorization' => 'Bearer ' . config('app.bearer')]
        );

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null
        ]);

        $response->assertStatus(200);
    }
}
