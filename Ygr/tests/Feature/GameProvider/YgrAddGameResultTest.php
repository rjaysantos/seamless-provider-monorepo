<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class YgrAddGameResultTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ygr.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_addGameResult_validRequest_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID'
        ]);

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
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
            'ext_id' => 'testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00'
        ]);

        Carbon::setTestNow();
    }

    #[DataProvider('betAndSettleParams')]
    public function test_addGameResult_invalidRequest_expectedData($parameter)
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
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
            ['wagersTime']
        ];
    }

    public function test_addGameResult_tokenNotFound_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'connectToken' => 'invalidToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
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

    public function test_addGameResult_transactionAlreadyExists_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID'
        ]);

        DB::table('ygr.reports')->insert([
            'ext_id' => 'testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 500.00,
            'payoutAmount' => 0.00,
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
            'ext_id' => 'testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00'
        ]);

        $this->assertDatabaseMissing('ygr.reports', [
            'ext_id' => 'testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 500.00,
            'bet_winlose' => -500.00,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00'
        ]);

        Carbon::setTestNow();
    }

    public function test_addGameResult_walletErrorBalance_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID'
        ]);

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
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

    public function test_addGameResult_insufficientFund_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID'
        ]);

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
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

    public function test_addGameResult_walletErrorWagerAndPayout_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
            'game_code' => 'testGameID'
        ]);

        $request = [
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
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
            'ext_id' => 'testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00'
        ]);

        Carbon::setTestNow();
    }
}
