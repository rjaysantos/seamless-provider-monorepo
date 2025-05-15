<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class JdbPlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE jdb.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE jdb.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE jdb.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_validData_expected()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ];

        Http::fake([
            '/apiRequest.do' => Http::response(json_encode([
                'status' => "0000",
                'path' => 'test_url?token=test'
            ]))
        ]);

        $response = $this->post('jdb/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_url?token=test',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('jdb.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://api.jdb711.com/apiRequest.do' &&
                $request['dc'] == 'COLS' &&
                $request['x'] == 'IvvWcm2DiIbG4q_dGRzp5AzJXptATlQhPjHuvpUREUPV2KV0Gz5L18pkbOs7jZiay8a2WH3AON8Fy' .
                'i4oricgaN0PT7_vYJyQoL7oVmtF7pc8pyvgJNRhA9AwvgaP3iKPq7RZy0OjqCEuQ2s51Lx_YMIrhURQMFQjaeb13Zgtzoh' .
                'ZH4oW5gnfNz6-91_2_WO6';
        });

        Carbon::setTestNow();
    }

    public function test_play_validDataArcade_expected()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '7-8001'
        ];

        Http::fake([
            '/apiRequest.do' => Http::response(json_encode([
                'status' => "0000",
                'path' => 'test_url?token=test'
            ]))
        ]);

        $response = $this->post('jdb/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_url?token=test',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('jdb.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'http://api.jdb711.com/apiRequest.do' &&
                $request['dc'] == 'COLS' &&
                $request['x'] == 'IvvWcm2DiIbG4q_dGRzp5AzJXptATlQhPjHuvpUREUPV2KV0Gz5L18pkbOs7jZiay8a2WH3AON8Fyi4' .
                'oricgaN0PT7_vYJyQoL7oVmtF7pc8pyvgJNRhA9AwvgaP3iKPq7RZy0OjqCEuQ2s51Lx_YIWe17j0zgfc0PRFmguDCU1Uzzw' .
                '324xMYAmOEU80y8fl';
        });

        Carbon::setTestNow();
    }

    public function test_play_walletBalanceNull_expected()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 987345312
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'USD',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ];

        $response = $this->post('jdb/in/play', $request, headers: [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Wallet Error'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequest_expected($param)
    {
        $request = [
            'playId' => 'abc123',
            'username' => 'user01',
            'currency' => 'USD',
            'language' => 'en',
            'device' => 1,
            'gameId' => 'game789'
        ];

        unset($request[$param]);

        $response = $this->post('jdb/in/play', $request, [
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
            ['device'],
            ['gameId']
        ];
    }

    public function test_play_invalidBearerToken_expectedData()
    {
        $request = [
            'playId' => 'abc123',
            'username' => 'user01',
            'currency' => 'USD',
            'language' => 'en',
            'device' => 1,
            'gameId' => 'game789'
        ];

        $response = $this->post('jdb/in/play', $request, [
            'Authorization' => 'Invalid Bearer Token',
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);

        $response->assertStatus(401);
    }

    public function test_play_thirdPartyApiError_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'playId' => 'abc123',
            'username' => 'user01',
            'currency' => 'USD',
            'language' => 'en',
            'device' => 1,
            'gameId' => 'game789'
        ];

        Http::fake([
            '/apiRequest.do' => Http::response(json_encode([
                'status' => "9999",
                'path' => 'invalid'
            ]))
        ]);

        $response = $this->post('jdb/in/play', $request, [
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
