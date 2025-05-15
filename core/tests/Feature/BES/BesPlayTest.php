<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class BesPlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE bes.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_validData_successReturn()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1,
            'host' => 'test-host'
        ];

        Http::fake([
            '/api/game/getKey' => Http::response(json_encode([
                'returnurl' => 'test-url?test=test',
                'status' => 1
            ]))
        ]);

        $response = $this->post('bes/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url?test=test&aid=besoftaixswuat&gid=1&lang=en&return_url=test-host',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bes.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);
    }

    /**
     * @dataProvider playParams
     */
    public function test_play_incompleteRequestParameters_invalidRequestResponse($param)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1,
            'host' => 'test-host'
        ];

        unset($request[$param]);

        $response = $this->post('bes/in/play', $request, [
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

    public static function playParams()
    {
        return [
            ['playId'],
            ['username'],
            ['currency'],
            ['language'],
            ['gameId'],
            ['device'],
            ['host'],
        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1,
            'host' => 'test-host'
        ];

        Http::fake([
            '/api/game/getKey' => Http::response(json_encode([
                'returnurl' => 'test-url?test=test',
                'status' => 1
            ]))
        ]);

        $response = $this->post('bes/in/play', $request, [
            'Authorization' => 'Bearer invalid_token'
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'data' => null,
            'error' => 'Authorization failed.'
        ]);

        $response->assertStatus(401);
    }

    public function test_play_thirdPartyError_ThirdPartyErrorResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1,
            'host' => 'test-host'
        ];

        Http::fake([
            '/api/game/getKey' => Http::response('', 500)
        ]);

        $response = $this->post('bes/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bes.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);
    }

    public function test_play_invalidResponseFormat_ThirdPartyErrorResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1,
            'host' => 'test-host'
        ];

        Http::fake([
            '/api/game/getKey' => Http::response(json_encode([
                'returnurl' => 'test-url?test=test',
            ]))
        ]);

        $response = $this->post('bes/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bes.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);
    }

    public function test_play_apiResponseStatusNot1_ThirdPartyErrorResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1,
            'host' => 'test-host'
        ];

        Http::fake([
            '/api/game/getKey' => Http::response(json_encode([
                'returnurl' => 'test-url?test=test',
                'status' => 999
            ]))
        ]);

        $response = $this->post('bes/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bes.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);
    }
}
