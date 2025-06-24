<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

class OrsPlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ors.players RESTART IDENTITY;');
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
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        Carbon::setTestNow(Carbon::parse('2024-04-18 10:00:00'));

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        Http::fake([
            '/api/v2/platform/games/launch*' => Http::response(json_encode([
                'rs_code' => 'S-100',
                'rs_message' => 'success',
                'game_link' => 'test-launch-url'
            ]))
        ]);

        $response = $this->post('ors/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-launch-url',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ors.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => '1',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://xyz.pwqr820.com:9003/api/v2/platform/games/launch?player_id=testPlayID&timestamp=1713405600&nickname=testPlayID&token=testToken&lang=en&game_id=1&betlimit=164&signature=b406bd96e260b57ccf06f6964019f732' &&
                $request->hasHeader('key', 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x') &&
                $request->hasHeader('operator-name', 'mog052testidrslot') &&
                $request['player_id'] == 'testPlayID' &&
                $request['nickname'] == 'testPlayID' &&
                $request['token'] == 'testToken' &&
                $request['lang'] == 'en' &&
                $request['game_id'] == '1' &&
                $request['betlimit'] == 164;
        });
    }

    public function test_play_validRequestPlayerAlreadyExists_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        Carbon::setTestNow('2024-04-18 10:00:00');

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        Http::fake([
            '/api/v2/platform/games/launch*' => Http::response(json_encode([
                'rs_code' => 'S-100',
                'rs_message' => 'success',
                'game_link' => 'test-launch-url'
            ]))
        ]);

        $response = $this->post('ors/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-launch-url',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ors.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => '1',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://xyz.pwqr820.com:9003/api/v2/platform/games/launch?player_id=testPlayID&timestamp=1713405600&nickname=testPlayID&token=testToken&lang=en&game_id=1&betlimit=164&signature=b406bd96e260b57ccf06f6964019f732' &&
                $request->hasHeader('key', 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x') &&
                $request->hasHeader('operator-name', 'mog052testidrslot') &&
                $request['player_id'] == 'testPlayID' &&
                $request['nickname'] == 'testPlayID' &&
                $request['lang'] == 'en' &&
                $request['game_id'] == '1' &&
                $request['betlimit'] == 164;
        });
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequest_expectedData($param)
    {
        $expected = [
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'game_link' => 'test_url'
        ];

        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        unset($request[$param]);

        Http::fake([
            '/api/v2/platform/games/launch*' => Http::response(json_encode($expected))
        ]);

        $response = $this->post('ors/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer'),
        ]);

        $response->assertJson([
            'code' => 422,
            'data' => NULL,
            'error' => "invalid request format",
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
            ['gameId']
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
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        Http::fake([
            '/api/v2/platform/games/launch*' => Http::response()
        ]);

        $response = $this->post('ors/in/play', $request, [
            'Authorization' => 'Bearer invalid token',
        ]);

        $response->assertJson([
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => NULL,
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
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        $response = $this->post('ors/in/play', $request, [
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

    public function test_play_thirdPartyApiError_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        Http::fake([
            '/api/v2/platform/games/launch*' => Http::response(json_encode([
                'rs_code' => 'S-200',
                'error' => 'ThirdPartyApiErrorException',
            ]))
        ]);

        $response = $this->post('ors/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);
        $response->assertStatus(200);
    }

    #[DataProvider('gameLaunchResponse')]
    public function test_play_thirdPartyApiErrorMissingResponse_expectedData($param)
    {
        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

        $apiResponse = [
            'rs_code' => 'S-100',
            'game_link' => 'test-launch-url'
        ];

        unset($apiResponse[$param]);

        Http::fake([
            '/api/v2/platform/games/launch*' => Http::response(json_encode($apiResponse))
        ]);

        $response = $this->post('ors/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);
        $response->assertStatus(200);
    }

    public static function gameLaunchResponse()
    {
        return [
            ['rs_code'],
            ['game_link']
        ];
    }
}
