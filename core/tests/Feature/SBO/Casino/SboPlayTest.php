<?php

use Tests\TestCase;
use App\Models\SboPlayer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SboPlayTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_play_stgValidDataNoPlayer_expected()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            '/web-root/restricted/player/register-player.aspx' => Http::response(json_encode([
                'serverId' => 'GA-staging',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        Http::fake([
            '/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-staging',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.players', [
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.78.90'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-demo-yy.568win.com/web-root/restricted/player/register-player.aspx' &&
                $request['CompanyKey'] == 'F34A561C731843F5A0AD5FA589060FBB' &&
                $request['ServerId'] == 'GA-staging' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Agent'] == 'test_agent_ido_01';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-demo-yy.568win.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == 'F34A561C731843F5A0AD5FA589060FBB' &&
                $request['ServerId'] == 'GA-staging' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_stgValidDataHasPlayer_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'ip_address' => '999.888.77.66',
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            '/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-staging',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-demo-yy.568win.com/web-root/restricted/player/register-player.aspx';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-demo-yy.568win.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == 'F34A561C731843F5A0AD5FA589060FBB' &&
                $request['ServerId'] == 'GA-staging' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodIDRValidDataNoPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' => Http::response(json_encode([
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.players', [
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.78.90'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Agent'] == 'AIXSWIDR_';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodIDRValidDataHasPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'IDR',
            'ip_address' => '999.888.77.66'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodBRLValidDataNoPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'BRL',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' => Http::response(json_encode([
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.players', [
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'BRL',
            'ip_address' => '123.456.78.90'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Agent'] == 'AIXSWBRL_';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodBRLValidDataHasPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'BRL',
            'ip_address' => '999.888.77.66'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'BRL',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodPHPValidDataNoPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'PHP',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' => Http::response(json_encode([
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Currency not supported',
            'data' => null,
        ]);

        $response->assertStatus(200);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Agent'] == 'AIXSWPHP_';
        });

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodPHPValidDataHasPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'PHP',
            'ip_address' => '999.888.77.66'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'PHP',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Currency not supported',
            'data' => null,
        ]);

        $response->assertStatus(200);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx';
        });

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodTHBValidDataNoPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'THB',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' => Http::response(json_encode([
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.players', [
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'THB',
            'ip_address' => '123.456.78.90'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Agent'] == 'AIXSWTHB_';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodTHBValidDataHasPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'THB',
            'ip_address' => '999.888.77.66'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'THB',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodUSDValidDataNoPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'USD',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' => Http::response(json_encode([
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.players', [
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'USD',
            'ip_address' => '123.456.78.90'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Agent'] == 'AIXSWUSD_';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodUSDValidDataHasPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'USD',
            'ip_address' => '999.888.77.66'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'USD',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodVNDValidDataNoPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'VND',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' => Http::response(json_encode([
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.players', [
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'VND',
            'ip_address' => '123.456.78.90'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Agent'] == 'AIXSWVND_';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_prodVNDValidDataHasPlayer_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'sbo_testPlayID',
            'currency' => 'VND',
            'ip_address' => '999.888.77.66'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'VND',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-production-01',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'https:testLaunchUrl&lang=en&oddstyle=ID&oddsmode=double&device=m',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/register-player.aspx';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/player/login.aspx' &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Username'] == 'sbo_testPlayID' &&
                $request['Portfolio'] == 'SportsBook';
        });
    }

    public function test_play_invalidBearerToken_expected()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer Invalid Token',
        ]);

        $response->assertJson([
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => NULL,
        ]);

        $response->assertStatus(401);
    }

    public function test_play_thirdPartyInvalidResponse_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0,
            'memberIp' => '123.456.78.90'
        ];

        Http::fake([
            '/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-staging',
                'error' => (object)[
                    'id' => 1,
                    'msg' => 'Data Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
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

    /**
     * @dataProvider sboPlayParams
     */
    public function test_play_missingRequestParameters_expected($param)
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 'd',
            'memberIp' => '123.456.78.90'
        ];

        unset($request[$param]);

        Http::fake([
            '/web-root/restricted/player/login.aspx' => Http::response(json_encode([
                'url' => 'testLaunchUrl',
                'serverId' => 'GA-staging',
                'error' => (object)[
                    'id' => 0,
                    'msg' => 'No Error'
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'code' => 422,
            'data' => NULL,
            'error' => "invalid request format",
        ]);

        $response->assertStatus(200);
    }

    public static function sboPlayParams()
    {
        return [
            ['playId'],
            ['username'],
            ['currency'],
            ['language'],
            ['device'],
        ];
    }
}
