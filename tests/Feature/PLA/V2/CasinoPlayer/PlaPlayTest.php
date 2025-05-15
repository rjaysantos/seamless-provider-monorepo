<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;

class PlaPlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pla.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pla.playgame RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_validData_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ];

        Http::fake([
            '/from-operator/getGameLaunchUrl' => Http::response(json_encode([
                'code' => 200,
                'message' => '',
                'data' => [
                    'url' => 'testUrl.com'
                ],
                'timestamp' => '2024-05-01T03:09:18+00:00'
            ]))
        ]);

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        $response = $this->post('pla/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl.com',
            'error' => null
        ]);

        $this->assertDatabaseHas('pla.players', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'username' => 'testUsername'
        ]);

        $this->assertDatabaseHas('pla.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'PLAUCN_testToken',
            'expired' => 'FALSE'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '4d45ab9bee2ab5a924629d18e5f07606cbfeb5fd7c0d2de2b13cab42ee966a1c') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PLAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'testGameID' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PLAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });
    }

    public function test_play_noPlayerValidData_expectedData()
    {
        DB::table('pla.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pla.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'oldToken',
            'expired' => 'FALSE'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ];

        Http::fake([
            '/from-operator/getGameLaunchUrl' => Http::response(json_encode([
                'code' => 200,
                'message' => '',
                'data' => [
                    'url' => 'testUrl.com'
                ],
                'timestamp' => '2024-05-01T03:09:18+00:00'
            ]))
        ]);

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        $response = $this->post('pla/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl.com',
            'error' => null
        ]);

        $this->assertDatabaseHas('pla.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'PLAUCN_testToken',
            'expired' => 'FALSE'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '4d45ab9bee2ab5a924629d18e5f07606cbfeb5fd7c0d2de2b13cab42ee966a1c') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PLAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'testGameID' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PLAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });
    }

    /**
     * @dataProvider requestData
     */
    public function test_play_invalidRequest_expectedData($requestParams)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ];

        unset($request[$requestParams]);

        $response = $this->post('pla/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'invalid request format'
        ]);
    }

    public static function requestData()
    {
        return [
            ['playId'],
            ['username'],
            ['currency'],
            ['language'],
            ['gameId'],
            ['device']
        ];
    }

    public function test_play_invalidBearerToken_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ];

        $response = $this->post('pla/in/play', $request, [
            'Authorization' => 'Bearer ' . 'invalidBearerToken',
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);
    }

    public function test_play_thirdPartyApiErrorNoCodeField_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ];

        Http::fake([
            '/from-operator/getGameLaunchUrl' => Http::response(json_encode([
                'message' => 'The request id field is required.',
                'errors' => (object) [
                    'requestId' => [
                        'The request id field is required.'
                    ]
                ]
            ]))
        ]);

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        $response = $this->post('pla/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $this->assertDatabaseHas('pla.players', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'username' => 'testUsername'
        ]);

        $this->assertDatabaseHas('pla.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'PLAUCN_testToken',
            'expired' => 'FALSE'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '4d45ab9bee2ab5a924629d18e5f07606cbfeb5fd7c0d2de2b13cab42ee966a1c') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PLAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'testGameID' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PLAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });
    }

    public function test_play_thirdPartyApiErrorCodeNot200_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ];

        Http::fake([
            '/from-operator/getGameLaunchUrl' => Http::response(json_encode([
                'code' => 401,
                'message' => '',
                'data' => [
                    'url' => 'testUrl.com'
                ],
                'timestamp' => '2024-05-01T03:09:18+00:00'
            ]))
        ]);

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        $response = $this->post('pla/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $this->assertDatabaseHas('pla.players', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'username' => 'testUsername'
        ]);

        $this->assertDatabaseHas('pla.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'PLAUCN_testToken',
            'expired' => 'FALSE'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '4d45ab9bee2ab5a924629d18e5f07606cbfeb5fd7c0d2de2b13cab42ee966a1c') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PLAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'testGameID' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PLAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });
    }
}
