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
        DB::statement('TRUNCATE TABLE ors.playgame RESTART IDENTITY;');
    }

    public function test_play_validDataNoPlayerYet_expectedData()
    {
        $request = [
            'branchId' => 27,
            'playId' => 'qwe',
            'username' => 'esterc5',
            'currency' => 'IDR',
            'language' => 'en',
            'country' => 'PH',
            'gameId' => '76',
            'host' => 'test',
            'device' => 1,
            'isTrial' => 0
        ];

        Carbon::setTestNow(Carbon::parse('2024-04-18 10:00:00'));

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'test';
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
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-launch-url',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ors.players', [
            'play_id' => 'qwe',
            'username' => 'esterc5',
            'currency' => 'IDR',
        ]);

        $this->assertDatabaseHas('ors.playgame', [
            'play_id' => 'qwe',
            'token' => 'test'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://xyz.pwqr820.com:9003/api/v2/platform/games/launch?player_id=qwe&timestamp=1713405600&nickname=qwe&token=test&lang=en&game_id=76&betlimit=164&signature=a30efe42c41d9009d0cb550b54211939' &&
                $request->hasHeader('key', 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x') &&
                $request->hasHeader('operator-name', 'mog052testidrslot') &&
                $request['player_id'] == 'qwe' &&
                $request['nickname'] == 'qwe' &&
                $request['lang'] == 'en' &&
                $request['game_id'] == 76 &&
                $request['betlimit'] == 164;
        });
    }

    public function test_play_validDataHasPlayer_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'qwe',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.playgame')->insert([
            'play_id' => 'qwe',
            'token' => 'oldToken',
            'expired' => 'false'
        ]);

        $request = [
            'branchId' => 27,
            'playId' => 'qwe',
            'username' => 'esterc5',
            'currency' => 'IDR',
            'language' => 'en',
            'country' => 'PH',
            'gameId' => '76',
            'host' => 'test',
            'device' => 1,
            'isTrial' => 0
        ];

        Carbon::setTestNow('2024-04-18 10:00:00');

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'test';
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
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-launch-url',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ors.playgame', [
            'play_id' => 'qwe',
            'token' => 'test'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://xyz.pwqr820.com:9003/api/v2/platform/games/launch?player_id=qwe&timestamp=1713405600&nickname=qwe&token=test&lang=en&game_id=76&betlimit=164&signature=a30efe42c41d9009d0cb550b54211939' &&
                $request->hasHeader('key', 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x') &&
                $request->hasHeader('operator-name', 'mog052testidrslot') &&
                $request['player_id'] == 'qwe' &&
                $request['nickname'] == 'qwe' &&
                $request['lang'] == 'en' &&
                $request['game_id'] == 76 &&
                $request['betlimit'] == 164;
        });
    }

    public function test_play_invalidBearerToken_expectedData()
    {
        $request = [
            'branchId' => 27,
            'playId' => 'qwe',
            'username' => 'esterc5',
            'currency' => 'IDR',
            'language' => 'en',
            'country' => 'PH',
            'gameId' => '76',
            'host' => 'test',
            'device' => 1,
            'isTrial' => 0
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

    #[DataProvider('playParams')]
    public function test_play_invalidRequest_expectedData($param, $message)
    {
        $expected = [
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'game_link' => 'test_url'
        ];

        $request = [
            'branchId' => 27,
            'playId' => 'qv2wj6w9zu027',
            'username' => 'esterc5',
            'currency' => 'IDR',
            'language' => 'id',
            'country' => 'id',
            'gameId' => '76',
            'host' => 'test',
            'device' => 1,
            'isTrial' => 0
        ];

        unset($request[$param]);

        Http::fake([
            '/api/v2/platform/games/launch*' => Http::response(json_encode($expected))
        ]);

        $response = $this->post('ors/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
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
            ['playId', 'play id'],
            ['username', 'username'],
            ['currency', 'currency'],
            ['language', 'language'],
            ['gameId', 'game id'],
        ];
    }

    public function test_play_thirdPartyApiError_expectedData()
    {
        $request = [
            'branchId' => 27,
            'playId' => 'qv2wj6w9zu027',
            'username' => 'esterc5',
            'currency' => 'IDR',
            'language' => 'id',
            'country' => 'id',
            'gameId' => '76',
            'host' => 'test',
            'device' => 1,
            'isTrial' => 0
        ];

        Http::fake([
            '/api/v2/platform/games/launch*' => Http::response(json_encode([
                'rs_code' => 'S-200',
                'error' => 'ThirdPartyApiErrorException',
            ]))
        ]);

        $response = $this->post('ors/in/play', $request, [
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
