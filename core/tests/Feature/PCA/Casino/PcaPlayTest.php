<?php

use Tests\TestCase;
use App\Models\PcaPlayer;
use App\Contracts\IRandomizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PcaPlayTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE pca.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.playgame RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_play_validDataNoPlayerYet_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'test;testGameID',
            'device' => 1,
            'host' => 'https://pesonawebtest.top'
        ];

        Http::fake([
            '/api/add/user' => Http::response(json_encode([
                'casino_user_id' => 'testPlayID',
                'username' => 'testPlayID'
            ]), 201)
        ]);

        Http::fake([
            '/api/request_link/real' => Http::response(json_encode([
                "message" => "URL was created.",
                "success" => true,
                "url" => "test_url?token=testToken",
                "token" => "testToken",
                "status" => "200"
            ]))
        ]);

        app()->bind(IRandomizer::class, function () {
            return new class implements IRandomizer {
                public function createToken(): string
                {
                    return 'testToken';
                }
            };
        });

        $response = $this->post('pca/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_url?token=testToken',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('pca.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.torrospins.com/api/add/user' &&
                $request->hasHeader('x-api-key', '7c14553b94179ffecce68c1c8b5d588fdc028d82962beccb8c1497288d8b0e75') &&
                $request['casino_user_id'] == 'testPlayID' &&
                $request['username'] == 'testUsername' &&
                $request['hash'] == 'e5bcb2360f973a6dc2054e468e9591d4';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.torrospins.com/api/request_link/real' &&
                $request->hasHeader('x-api-key', '7c14553b94179ffecce68c1c8b5d588fdc028d82962beccb8c1497288d8b0e75') &&
                $request['token'] == 'testToken' &&
                $request['game_name'] == 'test;testGameID' &&
                $request['user_id'] == 'testPlayID' &&
                $request['bank_id'] == 0 &&
                $request['currency'] == 'IDR' &&
                $request['quit_link'] == 'https://pesonawebtest.top' &&
                $request['device'] == 'desktop' &&
                $request['lang'] == 'en' &&
                $request['free_spin'] == 0 &&
                $request['lobby'] == true &&
                $request['hash'] == 'b3c68f1b0c0e6b599944b26cc2a972b7';
        });
    }

    public function test_play_validDataHasPlayer_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'test;testGameID',
            'device' => 1,
            'host' => 'https://pesonawebtest.top'
        ];

        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::fake([
            '/api/request_link/real' => Http::response(json_encode([
                "message" => "URL was created.",
                "success" => true,
                "url" => "test_url?token=testToken",
                "token" => "testToken",
                "status" => "200"
            ]))
        ]);

        app()->bind(IRandomizer::class, function () {
            return new class implements IRandomizer {
                public function createToken(): string
                {
                    return 'testToken';
                }
            };
        });


        $response = $this->post('pca/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_url?token=testToken',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('pca.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://api.torrospins.com/api/add/user' &&
                $request->hasHeader('x-api-key', '7c14553b94179ffecce68c1c8b5d588fdc028d82962beccb8c1497288d8b0e75') &&
                $request['casino_user_id'] == 'testPlayID' &&
                $request['username'] == 'testUsername' &&
                $request['hash'] == 'e5bcb2360f973a6dc2054e468e9591d4';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.torrospins.com/api/request_link/real' &&
                $request->hasHeader('x-api-key', '7c14553b94179ffecce68c1c8b5d588fdc028d82962beccb8c1497288d8b0e75') &&
                $request['token'] == 'testToken' &&
                $request['game_name'] == 'test;testGameID' &&
                $request['user_id'] == 'testPlayID' &&
                $request['bank_id'] == 0 &&
                $request['currency'] == 'IDR' &&
                $request['quit_link'] == 'https://pesonawebtest.top' &&
                $request['device'] == 'desktop' &&
                $request['lang'] == 'en' &&
                $request['free_spin'] == 0 &&
                $request['lobby'] == true &&
                $request['hash'] == 'b3c68f1b0c0e6b599944b26cc2a972b7';
        });
    }

    public function test_play_invalidBearerToken_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'test;testGameID',
            'device' => 1,
            'host' => 'https://pesonawebtest.top'
        ];

        $response = $this->post('pca/in/play', $request, [
            'Authorization' => 'invalid token',
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);
    }

    public function test_play_thirdPartyApiError_expectedData()
    {
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'test;testGameID',
            'device' => 1,
            'host' => 'https://pesonawebtest.top'
        ];

        Http::fake([
            '/api/request_link/real' => Http::response([], 401)
        ]);

        app()->bind(IRandomizer::class, function () {
            return new class implements IRandomizer {
                public function createToken(): string
                {
                    return 'testToken';
                }
            };
        });

        $response = $this->post('pca/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.torrospins.com/api/request_link/real' &&
                $request->hasHeader('x-api-key', '7c14553b94179ffecce68c1c8b5d588fdc028d82962beccb8c1497288d8b0e75') &&
                $request['token'] == 'testToken' &&
                $request['game_name'] == 'test;testGameID' &&
                $request['user_id'] == 'testPlayID' &&
                $request['bank_id'] == 0 &&
                $request['currency'] == 'IDR' &&
                $request['quit_link'] == 'https://pesonawebtest.top' &&
                $request['device'] == 'desktop' &&
                $request['lang'] == 'en' &&
                $request['free_spin'] == 0 &&
                $request['lobby'] == true &&
                $request['hash'] == 'b3c68f1b0c0e6b599944b26cc2a972b7';
        });
    }

    /**
     * @dataProvider playParams
     */
    public function test_play_invalidRequest_expectedDataData($param)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'test;testGameID',
            'device' => 1,
            'host' => 'https://pesonawebtest.top'
        ];

        unset($request[$param]);

        $response = $this->post('pca/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'invalid request format'
        ]);
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
            ['host']
        ];
    }
}
