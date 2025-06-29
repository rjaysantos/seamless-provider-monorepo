<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class Hg5PlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_validData_successResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        Http::fake([
            '/GrandPriest/gamelink' => Http::response(json_encode([
                'data' => [
                    'url' => 'testUrl',
                    'token' => 'testToken',
                ],
                'status' => [
                    'code' => '0'
                ]
            ]))
        ]);

        $response = $this->post('/hg5/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hg5.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/gamelink' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['account'] == 'testPlayID' &&
                $request['gamecode'] == 'testGameID';
        });
    }

    public function test_play_validDataHasPlayer_successResponse()
    {
        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'oldTestToken'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        Http::fake([
            '/GrandPriest/gamelink' => Http::response(json_encode([
                'data' => [
                    'url' => 'testUrl',
                    'token' => 'newTestToken',
                ],
                'status' => [
                    'code' => '0'
                ]
            ]))
        ]);

        $response = $this->post('/hg5/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hg5.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'oldTestToken'
        ]);

        $this->assertDatabaseHas('hg5.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'newTestToken'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/gamelink' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['account'] == 'testPlayID' &&
                $request['gamecode'] == 'testGameID';
        });
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestParameters_invalidRequestResponse($param)
    {
        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        unset($request[$param]);

        $response = $this->post('/hg5/in/play', $request, [
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
            ['gameId']
        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        $response = $this->post('/hg5/in/play', $request, [
            'Authorization' => 'Bearer invalid token',
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
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        Http::fake([
            '/GrandPriest/gamelink' => Http::response('', 500)
        ]);

        $response = $this->post('/hg5/in/play', $request, [
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

    #[DataProvider('gameLinkResponseParams')]
    public function test_play_thirdPartyMissingResponse_thirdPartyErrorResponse($param)
    {
        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        $apiResponse = [
            'data' => [
                'url' => 'testUrl',
            ],
            'status' => [
                'code' => '0'
            ]
        ];

        if ($param === 'data' || $param === 'status')
            unset($apiResponse[$param]);
        else if ($param === 'url')
            unset($apiResponse['data'][$param]);
        else if ($param === 'code')
            unset($apiResponse['status'][$param]);

        Http::fake([
            '/GrandPriest/gamelink' => Http::response(json_encode($apiResponse))
        ]);

        $response = $this->post('/hg5/in/play', $request, [
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

    #[DataProvider('gameLinkResponseParams')]
    public function test_play_thirdPartyInvalidResponse_thirdPartyErrorResponse($param, $value)
    {
        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        $apiResponse = [
            'data' => [
                'url' => 'testUrl',
            ],
            'status' => [
                'code' => '0'
            ]
        ];

        if ($param === 'data' || $param === 'status')
            $apiResponse[$param] = $value;
        else if ($param === 'url')
            $apiResponse['data'][$param] = $value;
        else if ($param === 'code')
            $apiResponse['status'][$param] = $value;

        Http::fake([
            '/GrandPriest/gamelink' => Http::response(json_encode($apiResponse))
        ]);

        $response = $this->post('/hg5/in/play', $request, [
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

    public static function gameLinkResponseParams()
    {
        return [
            ['data', 'test'],
            ['url', 123],
            ['status', 'test'],
            ['code', 123]
        ];
    }

    public function test_play_thirdPartyResponseCodeNot0_thirdPartyErrorResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        Http::fake([
            '/GrandPriest/gamelink' => Http::response(json_encode([
                'status' => [
                    'code' => '999'
                ]
            ]))
        ]);

        $response = $this->post('/hg5/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://wallet-csw-test.hg5games.com:5500/GrandPriest/gamelink' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQY' .
                    'XJlbnRJZCI6ImFpeGFkbWluIiwicGFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyT' .
                    'KgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk') &&
                $request['account'] == 'testPlayID' &&
                $request['gamecode'] == 'testGameID';
        });
    }
}
