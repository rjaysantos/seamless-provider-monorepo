<?php

use Tests\TestCase;
use Illuminate\Support\Str;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class SabVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_stgValidRequestSportsbook_expectedData()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '1'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'payout-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
                'game_code' => '1',
                'sports_type' => 'Soccer',
                'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                'match' => 'Netherlands vs Portugal',
                'hdp' => 3.4,
                'odds' => 1.24,
                'result' => 'won',
                'flag' => 'settled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID'
        ];

        $response = $this->post('/sab/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertTrue(
            Str::contains(
                $response->getContent(),
                'visual'
            )
        );
    }

    #[DataProvider('visualParams')]
    public function test_visual_incompleteParameters_expectedData($key)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID'
        ];

        unset($request[$key]);

        $response = $this->post('/sab/in/visual', $request, [
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
            ['bet_id']
        ];
    }

    public function test_visual_invalidBearerToken_expectedData()
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID'
        ];

        $response = $this->post('/sab/in/visual', $request, [
            'Authorization' => 'invalid_bearer_token',
        ]);

        $response->assertJson([
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => NULL,
        ]);

        $response->assertStatus(401);
    }

    public function test_visual_playIdNotFound_expectedData()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '1'
            ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'testTransactionID'
        ];

        $response = $this->post('/sab/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Player not found'
        ]);

        $response->assertStatus(200);

        $this->assertFalse(
            Str::contains(
                $response->getContent(),
                'visual'
            )
        );
    }

    public function test_visual_betIdNotFound_expectedData()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '1'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'payout-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
                'game_code' => '1',
                'sports_type' => 'Soccer',
                'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                'match' => 'Netherlands vs Portugal',
                'hdp' => 3.4,
                'odds' => 1.24,
                'result' => 'won',
                'flag' => 'settled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'non-existent-transaction-id'
        ];

        $response = $this->post('/sab/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null,
        ]);

        $response->assertStatus(200);

        $this->assertFalse(
            Str::contains(
                $response->getContent(),
                'visual'
            )
        );
    }
}
