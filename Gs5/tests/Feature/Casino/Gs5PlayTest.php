<?php

use Providers\Gs5\Credentials\Gs5Staging;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;

class Gs5PlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE gs5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE gs5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_validDataNoPlayerRecordYet_successResponse()
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
            'language' => 'en'
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        $response = $this->post('/gs5/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer'),
        ]);

        $credentials = new Gs5Staging;
        $expectedLaunchUrl =  $credentials->getApiUrl() . '/launch/?' . http_build_query([
            'host_id' => '81f89497d43f2eac684cb226f879c26c',
            'game_id' => 'testGameID',
            'lang' => 'en-US',
            'access_token' => 'testToken'
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => $expectedLaunchUrl,
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gs5.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID'
        ]);
    }

    public function test_play_validDataHasPlayer_successResponse()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'oldTestToken',
            'game_code' => 'oldTestGameID'
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
            'language' => 'en'
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'newTestToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        $response = $this->post('/gs5/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer'),
        ]);

        $credentials = new Gs5Staging;
        $expectedLaunchUrl =  $credentials->getApiUrl() . '/launch/?' . http_build_query([
            'host_id' => '81f89497d43f2eac684cb226f879c26c',
            'game_id' => 'testGameID',
            'lang' => 'en-US',
            'access_token' => 'newTestToken'
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => $expectedLaunchUrl,
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('gs5.players', [
            'play_id' => 'testPlayID',
            'token' => 'oldTestToken'
        ]);

        $this->assertDatabaseHas('gs5.players', [
            'play_id' => 'testPlayID',
            'token' => 'newTestToken',
            'game_code' => 'testGameID'
        ]);
    }

    #[DataProvider('casinoLanguages')]
    public function test_play_validDataDifferentLanguages_successResponse($lang, $expectedLanguage)
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
            'language' => $lang
        ];

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testToken';
            }
        };

        app()->bind(Randomizer::class, $randomizer::class);

        $response = $this->post('/gs5/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer'),
        ]);

        $credentials = new Gs5Staging;
        $expectedLaunchUrl =  $credentials->getApiUrl() . '/launch/?' . http_build_query([
            'host_id' => '81f89497d43f2eac684cb226f879c26c',
            'game_id' => 'testGameID',
            'lang' => $expectedLanguage,
            'access_token' => 'testToken'
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => $expectedLaunchUrl,
            'error' => null
        ]);

        $response->assertStatus(200);
    }

    public static function casinoLanguages()
    {
        return [
            ['en', 'en-US'],
            ['tl', 'en-US'],
            ['id', 'id-ID'],
            ['th', 'th-TH'],
            ['vn', 'vi-VN'],
            ['cn', 'zh-CN']
        ];
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
            'language' => 'en'
        ];

        unset($request[$param]);

        $response = $this->post('/gs5/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer'),
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
            'language' => 'en'
        ];

        $response = $this->post('/gs5/in/play', $request, [
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
}
