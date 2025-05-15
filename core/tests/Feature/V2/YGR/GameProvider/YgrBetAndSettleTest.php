<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;

class YgrBetAndSettleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ygr.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_betAndSettle_validRequest_expectedData()
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

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.123456789,
                    'status_code' => 2100
                ];
            }

            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1300.123456789,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('ygr/prov/transaction/addGameResult', $request);

        $response->assertJson([
            'data' => [
                'balance' => 1300.12,
                'currency' => 'IDR'
            ],
            'status' => [
                'code' => '0',
                'message' => 'Success',
                'dateTime' => '2021-01-01T00:00:00+08:00'
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ygr.reports', [
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        Carbon::setTestNow();
    }

    /**
     * @dataProvider betAndSettleParams
     */
    public function test_betAndSettle_invalidRequest_expectedData($parameter)
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ];

        unset($request[$parameter]);

        $response = $this->post('ygr/prov/transaction/addGameResult', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => '201',
                'message' => 'Bad parameter',
                'dateTime' => '2021-01-01T00:00:00+08:00'
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public static function betAndSettleParams()
    {
        return [
            ['connectToken'],
            ['roundID'],
            ['betAmount'],
            ['payoutAmount'],
            ['freeGame'],
            ['wagersTime']
        ];
    }

    public function test_betAndSettle_tokenNotFound_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'connectToken' => 'invalidToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ];

        $response = $this->post('ygr/prov/transaction/addGameResult', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => 102,
                'Message' => 'Sign Invalid',
                'dateTime' => '2021-01-01T00:00:00+08:00'
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_transactionAlreadyExists_expectedData()
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

        DB::table('ygr.reports')->insert([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 500.00,
            'payoutAmount' => 0.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ];

        $response = $this->post('ygr/prov/transaction/addGameResult', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => 208,
                'Message' => 'Round ID duplicated',
                'dateTime' => '2021-01-01T00:00:00+08:00'
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ygr.reports', [
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $this->assertDatabaseMissing('ygr.reports', [
            'trx_id' => 'testTransactionID',
            'bet_amount' => 500.00,
            'win_amount' => 0.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_walletErrorBalance_expectedData()
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

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('ygr/prov/transaction/addGameResult', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => '103',
                'message' => 'API failed',
                'dateTime' => '2021-01-01T00:00:00+08:00'
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_insufficientFund_expectedData()
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

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 10.123456789,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('ygr/prov/transaction/addGameResult', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => 204,
                'Message' => 'Insufficient balance',
                'dateTime' => '2021-01-01T00:00:00+08:00'
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_walletErrorWagerAndPayout_expectedData()
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

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.123456789,
                    'status_code' => 2100
                ];
            }

            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('ygr/prov/transaction/addGameResult', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => '103',
                'message' => 'API failed',
                'dateTime' => '2021-01-01T00:00:00+08:00'
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('ygr.reports', [
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        Carbon::setTestNow();
    }
}