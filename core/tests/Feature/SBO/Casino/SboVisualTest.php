<?php

use Tests\TestCase;
use App\Models\SboPlayer;
use App\Models\SboReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SboVisualTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_visual_stgValidRequestSportsbook_expectedData()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-demo-yy.568win.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == 'F34A561C731843F5A0AD5FA589060FBB' &&
                $request['ServerId'] == 'GA-staging' &&
                $request['Portfolio'] == 'SportsBook' &&
                $request['Refno'] == 'testTransacID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_stgValidRequestVirtualSports_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'BVirtualSportsTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'BVirtualSportsTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-demo-yy.568win.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == 'F34A561C731843F5A0AD5FA589060FBB' &&
                $request['ServerId'] == 'GA-staging' &&
                $request['Portfolio'] == 'VirtualSports' &&
                $request['Refno'] == 'BVirtualSportsTransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_stgValidRequestMiniGame_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'FunkyGames_890896_Funky_fkg_TransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'FunkyGames_890896_Funky_fkg_TransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-demo-yy.568win.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == 'F34A561C731843F5A0AD5FA589060FBB' &&
                $request['ServerId'] == 'GA-staging' &&
                $request['Portfolio'] == 'SeamlessGame' &&
                $request['Refno'] == 'FunkyGames_890896_Funky_fkg_TransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_stgValidRequestRngGame_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'TKTestTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'TKTestTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-demo-yy.568win.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == 'F34A561C731843F5A0AD5FA589060FBB' &&
                $request['ServerId'] == 'GA-staging' &&
                $request['Portfolio'] == 'Games' &&
                $request['Refno'] == 'TKTestTransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodIDRValidRequestSportsbook_expectedData()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SportsBook' &&
                $request['Refno'] == 'testTransacID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodIDRValidRequestVirtualSports_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'BVirtualSportsTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'BVirtualSportsTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'VirtualSports' &&
                $request['Refno'] == 'BVirtualSportsTransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodIDRValidRequestMiniGame_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'FunkyGames_890896_Funky_fkg_TransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'FunkyGames_890896_Funky_fkg_TransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SeamlessGame' &&
                $request['Refno'] == 'FunkyGames_890896_Funky_fkg_TransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodBRLValidRequestSportsbook_expectedData()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'BRL',
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SportsBook' &&
                $request['Refno'] == 'testTransacID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodBRLValidRequestVirtualSports_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'BRL'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'BVirtualSportsTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'BVirtualSportsTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'VirtualSports' &&
                $request['Refno'] == 'BVirtualSportsTransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodBRLValidRequestMiniGame_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'BRL'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'FunkyGames_890896_Funky_fkg_TransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'FunkyGames_890896_Funky_fkg_TransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SeamlessGame' &&
                $request['Refno'] == 'FunkyGames_890896_Funky_fkg_TransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodPHPValidRequestSportsbook_expectedData()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'PHP',
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Currency not supported',
            'data' => null,
        ]);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SportsBook' &&
                $request['Refno'] == 'testTransacID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodPHPValidRequestVirtualSports_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'PHP'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'BVirtualSportsTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'BVirtualSportsTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Currency not supported',
            'data' => null,
        ]);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'VirtualSports' &&
                $request['Refno'] == 'BVirtualSportsTransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodPHPValidRequestMiniGame_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'PHP'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'FunkyGames_890896_Funky_fkg_TransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'FunkyGames_890896_Funky_fkg_TransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Currency not supported',
            'data' => null,
        ]);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SeamlessGame' &&
                $request['Refno'] == 'FunkyGames_890896_Funky_fkg_TransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodTHBValidRequestSportsbook_expectedData()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'THB',
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SportsBook' &&
                $request['Refno'] == 'testTransacID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodTHBValidRequestVirtualSports_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'THB'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'BVirtualSportsTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'BVirtualSportsTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'VirtualSports' &&
                $request['Refno'] == 'BVirtualSportsTransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodTHBValidRequestMiniGame_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'THB'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'FunkyGames_890896_Funky_fkg_TransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'FunkyGames_890896_Funky_fkg_TransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SeamlessGame' &&
                $request['Refno'] == 'FunkyGames_890896_Funky_fkg_TransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodUSDValidRequestSportsbook_expectedData()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'USD',
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SportsBook' &&
                $request['Refno'] == 'testTransacID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodUSDValidRequestVirtualSports_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'USD'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'BVirtualSportsTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'BVirtualSportsTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'VirtualSports' &&
                $request['Refno'] == 'BVirtualSportsTransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodUSDValidRequestMiniGame_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'USD'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'FunkyGames_890896_Funky_fkg_TransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'FunkyGames_890896_Funky_fkg_TransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SeamlessGame' &&
                $request['Refno'] == 'FunkyGames_890896_Funky_fkg_TransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodVNDValidRequestSportsbook_expectedData()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'VND',
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SportsBook' &&
                $request['Refno'] == 'testTransacID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodVNDValidRequestVirtualSports_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'VND'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'BVirtualSportsTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'BVirtualSportsTransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'VirtualSports' &&
                $request['Refno'] == 'BVirtualSportsTransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_prodVNDValidRequestMiniGame_expected()
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'VND'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'FunkyGames_890896_Funky_fkg_TransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'FunkyGames_890896_Funky_fkg_TransactionID',
            'currency' => 'IDR'
        ];

        Http::fake([
            'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://ex-api-yy.xxttgg.com/web-root/restricted/report/get-bet-payload.aspx' &&
                $request->hasHeader('Content-Type', 'application/json') &&
                $request['CompanyKey'] == '7DC996ABC2E642339147E5F776A3AE85' &&
                $request['ServerId'] == 'GA-production-01' &&
                $request['Portfolio'] == 'SeamlessGame' &&
                $request['Refno'] == 'FunkyGames_890896_Funky_fkg_TransactionID' &&
                $request['Language'] == 'EN';
        });
    }

    public function test_visual_invalidBearerToken_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);
        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . 'Invalid Bearer Token',
        ]);

        $response->assertJson([
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => NULL,
        ]);

        $response->assertStatus(401);
    }

    public function test_visual_playerNotFound_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Player not found'
        ]);
    }

    public function test_visual_transactionNotFound_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'wrongTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        // $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Transaction not found'
        ]);
    }

    public function test_visual_thirdPartyInvalidResponse_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransacID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'failed-url',
                'error' => [
                    'id' => 405,
                ]
            ]))
        ]);

        $response = $this->post('sbo/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);
    }

    /**
     * @dataProvider visualParams
     */
    public function test_visual_missingParameterRequest_expected($param)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '',
            'txn_id' => 'testTransacID',
            'currency' => 'IDR'
        ];

        unset($request[$param]);

        $response = $this->post('sbo/in/visual', $request, [
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

    public static function visualParams()
    {
        return [
            ['play_id'],
            ['txn_id'],
            ['currency'],
        ];
    }
}
