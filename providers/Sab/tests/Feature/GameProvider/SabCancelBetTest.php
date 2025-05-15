<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SabCancelBetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_cancelBet_validData_cancelBetResponse()
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID1',
                'trx_id' => 'testTransactionID1',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID2',
                'trx_id' => 'testTransactionID2',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null,
            'balance' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID1',
            'trx_id' => 'testTransactionID1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    #[DataProvider('currencyConversionParams')]
    public function test_cancelBet_validDataCurrencyConversion_cancelBetResponse($currency, $value)
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => $currency,
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID1',
                'trx_id' => 'testTransactionID1',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => $currency,
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID2',
                'trx_id' => 'testTransactionID2',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => $currency,
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null,
            'balance' => $value
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID1',
            'trx_id' => 'testTransactionID1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public static function currencyConversionParams()
    {
        return [
            ['IDR', 1],
            ['THB', 1000],
            ['VND', 1],
            ['BRL', 1000],
            ['USD', 1000]
        ];
    }

    public function test_cancelBet_userIdNotExists_playerNotFoundResponse()
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
                'userId' => 'non-existent-username',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_invalidKey_invalidKeyResponse()
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
                'bet_id' => 'testBetOperationID-testTransactionID1',
                'trx_id' => 'testTransactionID1',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID2',
                'trx_id' => 'testTransactionID2',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('sab/prov/cancelbet', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_allRefIdNotExists_transactionNotFoundResponse()
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
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 504,
            'msg' => 'No Such Ticket'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_oneRefIdNotExists_transactionNotFoundResponse()
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID2',
                'trx_id' => 'testTransactionID2',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 504,
            'msg' => 'No Such Ticket'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID1',
            'trx_id' => 'testTransactionID1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_cancelBet_allFlagNotWaiting_invalidTransactionStatusResponse()
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID1',
                'trx_id' => 'testTransactionID1',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
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

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID2',
                'trx_id' => 'testTransactionID2',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
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
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 309,
            'msg' => 'Invalid Transaction Status',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID1',
            'trx_id' => 'testTransactionID1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_cancelBet_oneFlagNotWaiting_invalidTransactionStatusResponse()
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID1',
                'trx_id' => 'testTransactionID1',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
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

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID2',
                'trx_id' => 'testTransactionID2',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 309,
            'msg' => 'Invalid Transaction Status',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID1',
            'trx_id' => 'testTransactionID1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_cancelBet_allCancelBetTransactionAlreadyExists_cancelBetResponse()
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testOperationID-testTransactionID1',
                'trx_id' => 'testTransactionID1',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testOperationID-testTransactionID2',
                'trx_id' => 'testTransactionID2',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null,
            'balance' => 1
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_oneCancelBetTransactionAlreadyExists_cancelBetResponse()
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID1',
                'trx_id' => 'testTransactionID1',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testOperationID-testTransactionID2',
                'trx_id' => 'testTransactionID2',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null,
            'balance' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID1',
            'trx_id' => 'testTransactionID1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Handicap',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_cancelBet_invalidWalletResponse_walletErrorResponse()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
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
                'bet_id' => 'testBetOperationID-testTransactionID1',
                'trx_id' => 'testTransactionID1',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-testTransactionID2',
                'trx_id' => 'testTransactionID2',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2021-01-01 00:00:00',
                'bet_choice' => '-',
                'game_code' => 'Handicap',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ]
                ]
            ]
        ];

        $response = $this->post('sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('cancelBetParams')]
    public function test_cancelBet_incompleteRequestParameter_invalidRequestResponse($key)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID_cancelbet',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        elseif ($key === 'refId')
            unset($request['message']['txns'][0][$key]);
        else
            unset($request['message'][$key]);

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function cancelBetParams()
    {
        return [
            ['key'],
            ['message'],
            ['operationId'],
            ['userId'],
            ['updateTime'],
            ['txns'],
            ['refId']
        ];
    }
}
