<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;

class BesVisualTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE bes.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE bes.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visual_validRequest_successVisualResponse()
    {
        DB::table('bes.reports')
            ->insert([
                'trx_id' => 'test-betid',
                'bet_amount' => 100,
                'win_amount' => 0,
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]);

        $request = [
            'play_id' => 'test-playid',
            'bet_id' => 'test-betid',
            'txn_id' => 'test-txnid',
            'currency' => 'test-currency',
        ];

        Http::fake([
            '/api/game/getdetailsurl' => Http::response(json_encode([
                'logurl' => 'test-url',
                'status' => 1
            ]))
        ]);

        $response = $this->post('bes/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_invalidBearerToken_invalidBearerTokenResponse()
    {
        DB::table('bes.reports')
            ->insert([
                'trx_id' => 'test-betid',
                'bet_amount' => 100,
                'win_amount' => 0,
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]);

        $request = [
            'play_id' => 'test-playid',
            'bet_id' => 'test-betid',
            'txn_id' => 'test-txnid',
            'currency' => 'test-currency',
        ];

        Http::fake([
            '/api/game/getdetailsurl' => Http::response(json_encode([
                'logurl' => 'test-url',
                'status' => 1
            ]))
        ]);

        $response = $this->post('bes/in/visual', $request, [
            'Authorization' => 'Bearer invalid-token'
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'data' => null,
            'error' => 'Authorization failed.'
        ]);

        $response->assertStatus(401);
    }


    public function test_visual_betIDNotFound_transactionNotFoundResponse()
    {
        $request = [
            'play_id' => 'test-playid',
            'bet_id' => 'test-betid',
            'txn_id' => 'test-txnid',
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

    public function test_visual_thirdPartyError_thirdPartyErrorResponse()
    {
        DB::table('bes.reports')
            ->insert([
                'trx_id' => 'test-betid',
                'bet_amount' => 100,
                'win_amount' => 0,
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]);

        $request = [
            'play_id' => 'test-playid',
            'bet_id' => 'test-betid',
            'txn_id' => 'test-txnid',
            'currency' => 'test-currency',
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

    public function test_visual_invalidApiResponseFormat_thirdPartyErrorResponse()
    {
        DB::table('bes.reports')
            ->insert([
                'trx_id' => 'test-betid',
                'bet_amount' => 100,
                'win_amount' => 0,
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]);

        $request = [
            'play_id' => 'test-playid',
            'bet_id' => 'test-betid',
            'txn_id' => 'test-txnid',
            'currency' => 'test-currency',
        ];

        Http::fake([
            '/api/game/getdetailsurl' => Http::response(json_encode([
                'logurl' => 'test-url',
            ]))
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

    public function test_visual_apiResponseStatusNot1_thirdPartyErrorResponse()
    {
        DB::table('bes.reports')
            ->insert([
                'trx_id' => 'test-betid',
                'bet_amount' => 100,
                'win_amount' => 0,
                'created_at' => '2020-01-01 00:00:00',
                'updated_at' => '2020-01-01 00:00:00',
            ]);

        $request = [
            'play_id' => 'test-playid',
            'bet_id' => 'test-betid',
            'txn_id' => 'test-txnid',
            'currency' => 'test-currency',
        ];

        Http::fake([
            '/api/game/getdetailsurl' => Http::response(json_encode([
                'logurl' => 'test-url',
                'status' => 999
            ]))
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
}
