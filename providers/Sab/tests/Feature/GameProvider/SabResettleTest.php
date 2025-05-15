<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SabResettleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    #[DataProvider('validSettledFlags')]
    public function test_resettle_validDataTicketWin_successResponse($flag)
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 2000,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Netherlands',
                'game_code' => '1',
                'sports_type' => 'Soccer',
                'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                'match' => 'Netherlands vs Portugal',
                'hdp' => 3.4,
                'odds' => 1.24,
                'result' => 'win',
                'flag' => $flag,
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    #[DataProvider('currencyConversionParams')]
    public function test_resettle_validDataCurrencyConversion_successResponse($currency, $expectedAmount)
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => $currency,
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => $currency,
                'bet_amount' => 1000,
                'payout_amount' => 2000,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Netherlands',
                'game_code' => '1',
                'sports_type' => 'Soccer',
                'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                'match' => 'Netherlands vs Portugal',
                'hdp' => 3.4,
                'odds' => 1.24,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => $expectedAmount,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public static function currencyConversionParams()
    {
        return [
            ['IDR', 3000],
            ['THB', 3],
            ['VND', 3000],
            ['BRL', 3],
            ['USD', 3]
        ];
    }

    #[DataProvider('validSettledFlags')]
    public function test_resettle_validDataTicketLose_successResponse($flag)
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 3000,
                'bet_time' => '2021-09-08 16:49:32',
                'bet_choice' => 'Netherlands',
                'game_code' => 'Handicap',
                'sports_type' => 'Soccer',
                'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                'match' => 'Netherlands vs Portugal',
                'hdp' => 3.4,
                'odds' => 1.24,
                'result' => 'lose',
                'flag' => $flag,
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 0.00,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.00,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'lose',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public static function validSettledFlags()
    {
        return [
            ['settled'],
            ['resettled'],
        ];
    }

    public function test_resettle_userIdNotExists_playerNotFoundResponse()
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_invalidKey_invalidKeyResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 2000,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Netherlands',
                'game_code' => '1',
                'sports_type' => 'Soccer',
                'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                'match' => 'Netherlands vs Portugal',
                'hdp' => 3.4,
                'odds' => 1.24,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_txIdNotExists_transactionNotFoundResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 504,
            'msg' => 'No Such Ticket'
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_transactionAlreadyExists_transactionAlreadyExistFunction()
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 3000,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Netherlands',
                'game_code' => '1',
                'sports_type' => 'Soccer',
                'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                'match' => 'Netherlands vs Portugal',
                'hdp' => 3.4,
                'odds' => 1.24,
                'result' => 'win',
                'flag' => 'resettled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_invalidTransactionFlag_invalidTransactionStatusResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => '-',
                'game_code' => '1',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'running',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 309,
            'msg' => 'Invalid Transaction Status',
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_invalidWalletResponse_walletErrorResponse()
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 2000,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Netherlands',
                'game_code' => '1',
                'sports_type' => 'Soccer',
                'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                'match' => 'Netherlands vs Portugal',
                'hdp' => 3.4,
                'odds' => 1.24,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    #[DataProvider('resettleParams')]
    public function test_resettle_incompleteRequestParameter_invalidRequestResponse($key)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key = 'message')
            unset($request[$key]);
        elseif ($key === 'operationId')
            unset($request['message'][$key]);
        else
            unset($request['message']['txns'][0][$key]);

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function resettleParams()
    {
        return [
            ['key'],
            ['message'],
            ['operationId'],
            ['txns'],
            ['userId'],
            ['updateTime'],
            ['payout'],
            ['txId']
        ];
    }
}
