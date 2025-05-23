<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;

class YgrVerifyTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ygr.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.playgame RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_verifyToken_validRequest_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE',
            'status' => 'testGameID'
        ]);

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.123456789,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'connectToken' => 'testToken'
        ];

        $response = $this->post('ygr/prov/token/authorizationConnectToken', $request);

        $response->assertJson([
            'data' => [
                'ownerId' => 'AIX',
                'parentId' => 'AIX',
                'gameId' => 'testGameID',
                'userId' => 'testPlayID',
                'nickname' => 'testUsername',
                'currency' => 'IDR',
                'amount' => 1000.12
            ],
            'status' => [
                'code' => '0',
                'message' => 'Success',
                'dateTime' => '2021-01-01T00:00:00+08:00',
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_verifyToken_invalidRequest_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        $request = [
            'connectToken' => 'testToken'
        ];

        unset($request['connectToken']);

        $response = $this->post('ygr/prov/token/authorizationConnectToken', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => '201',
                'message' => 'Bad parameter',
                'dateTime' => '2021-01-01T00:00:00+08:00',
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_verifyToken_tokenNotFoundException_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE',
            'status' => 'testGameID'
        ]);

        $request = [
            'connectToken' => 'invalidToken'
        ];

        $response = $this->post('ygr/prov/token/authorizationConnectToken', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => 102,
                'Message' => 'Sign Invalid',
                'dateTime' => '2021-01-01T00:00:00+08:00',
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_verifyToken_walletException_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE',
            'status' => 'testGameID'
        ]);

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'connectToken' => 'testToken'
        ];

        $response = $this->post('ygr/prov/token/authorizationConnectToken', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => '103',
                'message' => 'API failed',
                'dateTime' => '2021-01-01T00:00:00+08:00',
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }
}