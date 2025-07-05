<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class YgrPlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ygr.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.playgame RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_validRequest_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        Http::fake([
            '/GameList' => Http::response(json_encode([
                'ErrorCode' => 0,
                'Data' => [
                    (object) [
                        'GameId' => 'testGameID',
                        'GameCategoryId' => 1
                    ],
                    (object) [
                        'GameId' => 'testGameID2',
                        'GameCategoryId' => 1
                    ],
                ]
            ])),

            '/launch*' => Http::response(json_encode([
                'ErrorCode' => 0,
                'Data' => (object) [
                    'Url' => 'testUrl.com'
                ]
            ]))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $this->assertDatabaseHas('ygr.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'status' => 'testGameID-slot'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/GameList';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/launch?token=testToken&language=id-ID' &&
                $request->hasHeader('Supplier', 'AIX') &&
                $request['token'] == 'testToken' &&
                $request['language'] == 'id-ID';
        });
    }

    public function test_play_validRequestPlayerAlreadyExists_expectedData()
    {
        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'oldToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        Http::fake([
            '/GameList' => Http::response(json_encode([
                'ErrorCode' => 0,
                'Data' => [
                    (object) [
                        'GameId' => 'testGameID',
                        'GameCategoryId' => 5
                    ],
                    (object) [
                        'GameId' => 'testGameID2',
                        'GameCategoryId' => 1
                    ],
                ]
            ])),

            '/launch*' => Http::response(json_encode([
                'ErrorCode' => 0,
                'Data' => (object) [
                    'Url' => 'testUrl.com'
                ]
            ]))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $this->assertDatabaseHas('ygr.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'status' => 'testGameID-arcade'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/GameList';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/launch?token=testToken&language=id-ID' &&
                $request->hasHeader('Supplier', 'AIX') &&
                $request['token'] == 'testToken' &&
                $request['language'] == 'id-ID';
        });
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequest_expectedData($parameter)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ];

        unset($request[$parameter]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
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
            ['gameId'],
            ['language']
        ];
    }

    public function test_play_invalidBearerTokenException_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ];

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . 'invalid Bearer Token'
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);

        $response->assertStatus(401);
    }

    public function test_play_GameListThirdPartyApiErrorException_expectedData()
    {
        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'oldToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        Http::fake([
            '/GameList' => Http::response(json_encode([
                'ErrorCode' => 1536482,
                'Data' => []
            ]))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $this->assertDatabaseHas('ygr.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'oldToken',
            'status' => 'oldGameID'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/GameList';
        });
    }

    #[DataProvider('apiResponseGameList')]
    public function test_play_invalidGameListAPIResponseThirdPartyApiErrorException_expectedData($parameter)
    {
        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'oldToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        $apiResponse = [
            'ErrorCode' => 0,
            'Data' => [
                [
                    'GameId' => 'testGameID',
                    'GameCategoryId' => 1
                ],
            ]
        ];

        if (isset($apiResponse[$parameter]) === true)
            unset($apiResponse[$parameter]);
        else
            unset($apiResponse['Data'][0][$parameter]);

        Http::fake([
            '/GameList' => Http::response(json_encode($apiResponse))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $this->assertDatabaseHas('ygr.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'oldToken',
            'status' => 'oldGameID'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/GameList';
        });
    }

    public static function apiResponseGameList()
    {
        return [
            ['ErrorCode'],
            ['Data'],
            ['GameId'],
            ['GameCategoryId']
        ];
    }

    public function test_play_launchThirdPartyApiErrorException_expectedData()
    {
        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'oldToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        Http::fake([
            '/GameList' => Http::response(json_encode([
                'ErrorCode' => 0,
                'Data' => [
                    (object) [
                        'GameId' => 'testGameID',
                        'GameCategoryId' => 1
                    ],
                    (object) [
                        'GameId' => 'testGameID2',
                        'GameCategoryId' => 1
                    ],
                ]
            ])),

            '/launch*' => Http::response(json_encode([
                'ErrorCode' => 8001,
                'Message' => '/token/authorizationConnectToken http request error!!!! ENOTFOUND(9998)',
                'Data' => null
            ]))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $this->assertDatabaseHas('ygr.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'status' => 'testGameID-slot'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/GameList';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/launch?token=testToken&language=id-ID' &&
                $request->hasHeader('Supplier', 'AIX') &&
                $request['token'] == 'testToken' &&
                $request['language'] == 'id-ID';
        });
    }

    #[DataProvider('apiResponseLaunch')]
    public function test_play_invalidLaunchAPIResponseThirdPartyApiErrorException_expectedData($parameter)
    {
        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'oldToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        $apiResponse = [
            'ErrorCode' => 0,
            'Data' => [
                'Url' => 'testUrl.com'
            ]
        ];

        if (isset($apiResponse[$parameter]) === true)
            unset($apiResponse[$parameter]);
        else
            unset($apiResponse['Data'][$parameter]);

        Http::fake([
            '/GameList' => Http::response(json_encode([
                'ErrorCode' => 0,
                'Data' => [
                    (object) [
                        'GameId' => 'testGameID',
                        'GameCategoryId' => 1
                    ],
                    (object) [
                        'GameId' => 'testGameID2',
                        'GameCategoryId' => 1
                    ],
                ]
            ])),

            '/launch*' => Http::response(json_encode($apiResponse))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $this->assertDatabaseHas('ygr.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'status' => 'testGameID-slot'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/GameList';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/launch?token=testToken&language=id-ID' &&
                $request->hasHeader('Supplier', 'AIX') &&
                $request['token'] == 'testToken' &&
                $request['language'] == 'id-ID';
        });
    }

    public static function apiResponseLaunch()
    {
        return [
            ['ErrorCode'],
            ['Data'],
            ['Url']
        ];
    }
}
