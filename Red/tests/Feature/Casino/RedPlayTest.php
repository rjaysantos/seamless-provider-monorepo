<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class RedPlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE red.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE red.reports RESTART IDENTITY;');
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
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en',
        ];

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

        Http::fake([
            '/auth' => Http::response([
                'launch_url' => 'testLaunchUrl.com',
                'user_id' => 456,
                'status' => 1
            ])
        ]);

        $response = $this->post('red/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testLaunchUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('red.players', [
            'user_id_provider' => '456',
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://uat.ps9games.com' . '/auth' &&
                $request->hasHeader('ag-code', 'MPO0114') &&
                $request->hasHeader('ag-token', '3BQ9KGFtnQtno4kz12bMP4UqhVqWlWtz') &&
                $request['user']['id'] == 123 &&
                $request['user']['name'] == 'testPlayID' &&
                $request['user']['balance'] == 1000.0 &&
                $request['user']['language'] == 'en' &&
                $request['user']['domain_url'] == 'testHost.com' &&
                $request['user']['currency'] == 'IDR' &&
                $request['prd']['id'] == 213 &&
                $request['prd']['type'] == '1' &&
                $request['prd']['is_mobile'] == false;
        });
    }

    public function test_play_validRequestPlayerAlreadyExists_expectedData()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 456,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testNewUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 1,
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en'
        ];

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

        Http::fake([
            '/auth' => Http::response([
                'launch_url' => 'testLaunchUrl.com',
                'user_id' => 456,
                'status' => 1
            ])
        ]);

        $response = $this->post('red/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'testLaunchUrl.com',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('red.players', [
            'user_id_provider' => '456',
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://uat.ps9games.com' . '/auth' &&
                $request->hasHeader('ag-code', 'MPO0114') &&
                $request->hasHeader('ag-token', '3BQ9KGFtnQtno4kz12bMP4UqhVqWlWtz') &&
                $request['user']['id'] == 123 &&
                $request['user']['name'] == 'testUsername' &&
                $request['user']['balance'] == 1000.0 &&
                $request['user']['language'] == 'en' &&
                $request['user']['domain_url'] == 'testHost.com' &&
                $request['user']['currency'] == 'IDR' &&
                $request['prd']['id'] == 213 &&
                $request['prd']['type'] == '1' &&
                $request['prd']['is_mobile'] == false;
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
            'gameId' => '1',
            'memberIp' => '127.0.0.1',
            'language' => 'en'
        ];

        unset($request[$parameter]);

        $response = $this->post('red/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => "invalid request format",
            'data' => NULL,
        ]);

        $response->assertStatus(200);
    }

    public static function playParams()
    {
        return [
            ['playId'],
            ['memberId'],
            ['username'],
            ['host'],
            ['currency'],
            ['device'],
            ['gameId']
        ];
    }

    public function test_play_invalidBearerToken_expectedData()
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
            'language' => 'en'
        ];

        $response = $this->post('red/in/play', $request, [
            'Authorization' => 'Bearer invalid token'
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => NULL
        ]);

        $response->assertStatus(401);
    }

    public function test_play_invalidWalletResponse_expectedData()
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
            'language' => 'en'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 1234
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('red/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Wallet Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_play_thirdPartyInvalidResponse_expectedData()
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
            'language' => 'en'
        ];

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

        Http::fake([
            '/auth' => Http::response([
                'status' => 0,
                'error' => 'INVALID_CURRENCY'
            ])
        ]);

        $response = $this->post('red/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('authenticateResponseParams')]
    public function test_play_thirdPartyMissingResponse_expectedData($parameter)
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
            'language' => 'en'
        ];

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

        $apiResponse = [
            'status' => 0,
            'user_id' => 456,
            'launch_url' => 'testLaunchUrl.com',
        ];

        unset($apiResponse[$parameter]);

        Http::fake(['/auth' => Http::response($apiResponse)]);

        $response = $this->post('red/in/play', $request, [
            'Authorization' => 'Bearer ' . config('app.bearer')
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    public static function authenticateResponseParams()
    {
        return [
            ['status'],
            ['user_id'],
            ['launch_url']
        ];
    }
}
