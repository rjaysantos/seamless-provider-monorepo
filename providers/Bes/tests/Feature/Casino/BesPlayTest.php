<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class BesPlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE bes.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_validRequestNoPlayerRecordYet_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'host' => 'testHost'
        ];

        Http::fake([
            '/api/game/getKey' => Http::response(json_encode([
                'returnurl' => 'testUrl?test=test',
                'status' => 1
            ]))
        ]);

        $response = $this->post('bes/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $expectedLaunchUrl = http_build_query([
            'aid' => 'besoftaixswuat',
            'gid' => 1,
            'lang' => 'en',
            'return_url' => 'testHost'
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => "testUrl?test=test&{$expectedLaunchUrl}",
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bes.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.stag-topgame.com/api/game/getKey' &&
                $request['cert'] == 'MCo9ktIXjOiGnhqlZVdy' &&
                $request['user'] == 'testPlayID' &&
                $request['extension1'] == 'besoftaixswuat';
        });
    }

    public function test_play_validRequestHasPlayer_expectedData()
    {
        DB::table('bes.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'host' => 'testHost'
        ];

        Http::fake([
            '/api/game/getKey' => Http::response(json_encode([
                'returnurl' => 'testUrl?test=test',
                'status' => 1
            ]))
        ]);

        $response = $this->post('bes/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $expectedLaunchUrl = http_build_query([
            'aid' => 'besoftaixswuat',
            'gid' => 1,
            'lang' => 'en',
            'return_url' => 'testHost'
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => "testUrl?test=test&{$expectedLaunchUrl}",
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.stag-topgame.com/api/game/getKey' &&
                $request['cert'] == 'MCo9ktIXjOiGnhqlZVdy' &&
                $request['user'] == 'testPlayID' &&
                $request['extension1'] == 'besoftaixswuat';
        });
    }

    #[DataProvider('playParams')]
    public function test_play_incompleteRequestParameters_invalidRequestResponse($param)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'host' => 'testHost'
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
            ['host']
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
            'host' => 'testHost'
        ];

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

    public function test_play_thirdPartyError_thirdPartyErrorResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'host' => 'testHost'
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
    }

    #[DataProvider('getKeyResponseParams')]
    public function test_play_thirdPartyMissingResponseParameter_thirdPartyErrorResponse($param)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'host' => 'testHost'
        ];

        $apiResponse = [
            'returnurl' => 'testUrl?test=test',
            'status' => 1
        ];

        unset($apiResponse[$param]);

        Http::fake([
            '/api/game/getKey' => Http::response(json_encode($apiResponse))
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
    }

    #[DataProvider('getKeyResponseParams')]
    public function test_play_thirdPartyInvalidResponseParameter_thirdPartyErrorResponse($param, $value)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'host' => 'testHost'
        ];

        Http::fake([
            '/api/game/getKey' => Http::response(json_encode([
                'returnurl' => 'testUrl?test=test',
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
    }

    public static function getKeyResponseParams()
    {
        return [
            ['returnurl', 123],
            ['status', 'test']
        ];
    }

    public function test_play_thirdPartyResponseStatusNot1_thirdPartyErrorResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'host' => 'testHost'
        ];

        Http::fake([
            '/api/game/getKey' => Http::response(json_encode([
                'returnurl' => 'testUrl?test=test',
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
    }
}
