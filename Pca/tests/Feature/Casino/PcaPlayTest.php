<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class PcaPlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.playgame RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_noPlayerValidData_expectedData()
    {
        $request = [
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

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

        $response = $this->post('pca/in/play', $request, ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '6e7928b51d2790e1b959fafc6a83f93d9eff411fc333' .
                    '84ac7faa0c8d54ad0774') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PCAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'ubal' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PCAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });

        $this->assertDatabaseHas('pca.players', [
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'username' => 'testUsername'
        ]);

        $this->assertDatabaseHas('pca.playgame', [
            'play_id' => 'testplayid',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);
    }

    public function test_play_HasPlayerValidData_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'testplayid',
            'token' => 'oldToken',
            'expired' => 'FALSE'
        ]);

        $request = [
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

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

        $response = $this->post('pca/in/play', $request, ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '6e7928b51d2790e1b959fafc6a83f93d9eff411fc333' .
                    '84ac7faa0c8d54ad0774') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PCAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'ubal' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PCAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });

        $this->assertDatabaseHas('pca.playgame', [
            'play_id' => 'testplayid',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequest_expectedData($requestParams)
    {
        $request = [
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ];

        unset($request[$requestParams]);

        $response = $this->post('pca/in/play', $request, [
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
            ['device']
        ];
    }

    public function test_play_invalidBearerToken_expectedData()
    {
        $request = [
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ];

        $response = $this->post('pca/in/play', $request, [
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

    public function test_play_thirdPartyApiErrorNoCodeField_expectedData()
    {
        $request = [
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

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

        $response = $this->post('pca/in/play', $request, ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '6e7928b51d2790e1b959fafc6a83f93d9eff411fc333' .
                    '84ac7faa0c8d54ad0774') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PCAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'ubal' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PCAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });

        $this->assertDatabaseHas('pca.players', [
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'username' => 'testUsername'
        ]);

        $this->assertDatabaseHas('pca.playgame', [
            'play_id' => 'testplayid',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);
    }

    #[DataProvider('getGameLaunchUrlResponseParams')]
    public function test_play_thirdPartyApiMissingResponseData_expectedData($parameter)
    {
        $request = [
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        $response = [
            'code' => 200,
            'data' => [
                'url' => 'testUrl.com'
            ]
        ];

        if (isset($response[$parameter]) === false)
            unset($response['data'][$parameter]);
        else
            unset($response[$parameter]);

        Http::fake([
            '/from-operator/getGameLaunchUrl' => Http::response(json_encode($response))
        ]);

        $response = $this->post('pca/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '6e7928b51d2790e1b959fafc6a83f93d9eff411fc333' .
                    '84ac7faa0c8d54ad0774') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PCAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'ubal' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PCAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });

        $this->assertDatabaseHas('pca.players', [
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'username' => 'testUsername'
        ]);

        $this->assertDatabaseHas('pca.playgame', [
            'play_id' => 'testplayid',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);
    }

    #[DataProvider('getGameLaunchUrlResponseParams')]
    public function test_play_thirdPartyApiInvalidResponseDataType_expectedData($parameter, $value)
    {
        $request = [
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        $response = [
            'code' => 200,
            'data' => [
                'url' => 'testUrl.com'
            ]
        ];

        if (isset($response[$parameter]) === false)
            $response['data'][$parameter] = $value;
        else
            $response[$parameter] = $value;

        Http::fake([
            '/from-operator/getGameLaunchUrl' => Http::response(json_encode($response))
        ]);

        $response = $this->post('pca/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '6e7928b51d2790e1b959fafc6a83f93d9eff411fc333' .
                    '84ac7faa0c8d54ad0774') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PCAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'ubal' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PCAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });

        $this->assertDatabaseHas('pca.players', [
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'username' => 'testUsername'
        ]);

        $this->assertDatabaseHas('pca.playgame', [
            'play_id' => 'testplayid',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);
    }

    public static function getGameLaunchUrlResponseParams()
    {
        return [
            ['code', 'invalid'],
            ['data', 'invalid'],
            ['url', 123]
        ];
    }

    public function test_play_thirdPartyApiErrorCodeNot200_expectedData()
    {
        $request = [
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

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

        $response = $this->post('pca/in/play', $request, ['Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api-uat.agmidway.net/from-operator/getGameLaunchUrl' &&
                $request->hasHeader('x-auth-kiosk-key', '6e7928b51d2790e1b959fafc6a83f93d9eff411fc33384ac7faa0c8d54ad0774') &&
                $request['serverName'] == 'AGCASTG' &&
                $request['username'] == 'PCAUCN_TESTPLAYID' &&
                $request['gameCodeName'] == 'ubal' &&
                $request['clientPlatform'] == 'web' &&
                $request['externalToken'] == 'PCAUCN_testToken' &&
                $request['language'] == 'en' &&
                $request['playMode'] == 1;
        });

        $this->assertDatabaseHas('pca.players', [
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'username' => 'testUsername'
        ]);

        $this->assertDatabaseHas('pca.playgame', [
            'play_id' => 'testplayid',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);
    }
}
