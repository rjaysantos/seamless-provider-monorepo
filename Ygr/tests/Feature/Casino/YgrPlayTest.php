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
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_validRequest_expectedData()
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
            'language' => 'id',
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        Http::fake([
            '/launch*' => Http::response(json_encode([
                'ErrorCode' => 0,
                'Data' => (object) [
                    'Url' => 'testUrl.com'
                ]
            ]))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
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
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID',
        ]);

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
            'currency' => 'IDR',
            'token' => 'oldToken',
            'game_code' => 'oldGameID'
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
            'language' => 'id',
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        Http::fake([
            '/launch*' => Http::response(json_encode([
                'ErrorCode' => 0,
                'Data' => (object) [
                    'Url' => 'testUrl.com'
                ]
            ]))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'oldToken',
            'game_code' => 'oldGameID',
        ]);

        $this->assertDatabaseHas('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID'
        ]);

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
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'id',
        ];

        unset($request[$parameter]);

        $response = $this->post('ygr/in/play', $request, [
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
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'id',
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

    public function test_play_invalidCurrency_expectedData()
    {
        config(['app.env' => 'PRODUCTION']);

        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'invalidCurrency',
            'device' => 1,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1',
            'language' => 'id',
        ];

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Currency not supported!',
            'data' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_play_thirdPartyApiErrorException_expectedData()
    {
        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'oldToken',
            'game_code' => 'oldGameID'
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
            'language' => 'id',
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        Http::fake([
            '/launch*' => Http::response(json_encode([
                'ErrorCode' => 8001,
                'Message' => '/token/authorizationConnectToken http request error!!!! ENOTFOUND(9998)',
                'Data' => null
            ]))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'oldToken',
            'game_code' => 'oldGameID',
        ]);

        $this->assertDatabaseHas('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/launch?token=testToken&language=id-ID' &&
                $request->hasHeader('Supplier', 'AIX') &&
                $request['token'] == 'testToken' &&
                $request['language'] == 'id-ID';
        });
    }

    #[DataProvider('apiResponse')]
    public function test_play_invalidAPIResponseThirdPartyApiErrorException_expectedData($parameter)
    {
        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'oldToken',
            'game_code' => 'oldGameID'
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
            'language' => 'id',
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
            '/launch*' => Http::response(json_encode($apiResponse))
        ]);

        $response = $this->post('ygr/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'oldToken',
            'game_code' => 'oldGameID',
        ]);

        $this->assertDatabaseHas('ygr.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://tyche8wmix-service.yahutech.com/launch?token=testToken&language=id-ID' &&
                $request->hasHeader('Supplier', 'AIX') &&
                $request['token'] == 'testToken' &&
                $request['language'] == 'id-ID';
        });
    }

    public static function apiResponse()
    {
        return [
            ['ErrorCode'],
            ['Data'],
            ['Url']
        ];
    }
}
