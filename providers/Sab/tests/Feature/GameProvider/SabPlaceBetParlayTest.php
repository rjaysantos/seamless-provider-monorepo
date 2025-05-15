<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SabPlaceBetParlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_placeBetParlay_validData_placeBetParlayResponse()
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID',
                    'licenseeTxId' => 'testTransactionID'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'waiting',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);
    }

    #[DataProvider('currencyConversionParams')]
    public function test_placeBetParlay_validDataCurrencyConversion_placeBetParlayResponse($currency, $value)
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID',
                    'licenseeTxId' => 'testTransactionID'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => $value,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'waiting',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);
    }

    public static function currencyConversionParams()
    {
        return [
            ['IDR', 1000],
            ['THB', 1],
            ['VND', 1000],
            ['BRL', 1],
            ['USD', 1]
        ];
    }

    public function test_placeBetParlay_validDataMultipleTransactions_placeBetParlayResponse()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 3000.0,
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 3,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID-1',
                        'betAmount' => 1
                    ],
                    [
                        'refId' => 'testTransactionID-2',
                        'betAmount' => 2
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID-1',
                    'licenseeTxId' => 'testTransactionID-1'
                ],
                [
                    'refId' => 'testTransactionID-2',
                    'licenseeTxId' => 'testTransactionID-2'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID-1',
            'trx_id' => 'testTransactionID-1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'waiting',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID-2',
            'trx_id' => 'testTransactionID-2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 2000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'waiting',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetParlay_validDataMultipleTransactions1stFailed_placeBetParlayResponse()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 5000.0,
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
                'bet_id' => 'testOperationID',
                'trx_id' => 'testTransactionID-1',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 3000,
                'payout_amount' => 0,
                'bet_time' => '2020-01-01 12:00:00',
                'bet_choice' => '-',
                'game_code' => 'Mix Parlay',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => '1',
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 5,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID-1',
                        'betAmount' => 3
                    ],
                    [
                        'refId' => 'testTransactionID-2',
                        'betAmount' => 2
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 1,
            'msg' => 'Duplicate Transaction'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-testTransactionID-2',
            'trx_id' => 'testTransactionID-2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 2000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'waiting',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetParlay_invalidUserId_playerNotFoundResponse()
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBetParlay_invalidKey_invalidKeyResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBetParlay_transactionAlreadyExists_transactionAlreadyExistResponse()
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
                'bet_id' => 'testOperationID-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2020-01-01 12:00:00',
                'bet_choice' => '-',
                'game_code' => 'Mix Parlay',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => '1',
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 1,
            'msg' => 'Duplicate Transaction'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBetParlay_insuficientBalance_insuficientFundResponse()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 900.0,
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 502,
            'msg' => 'Player Has Insufficient Funds'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBetParlay_walletError_walletErrorResponse()
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
                'play_id' => 'test-player-1',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('placeBetParlayParams')]
    public function test_placeBetParlay_incompleteRequestParameter_invalidRequestResponse($key)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message') {
            unset($request[$key]);
        } elseif ($key === 'refId' || $key === 'betAmount') {
            unset($request['message']['txns'][0][$key]);
        } else {
            unset($request['message'][$key]);
        }

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function placeBetParlayParams()
    {
        return [
            ['key'],
            ['message'],
            ['operationId'],
            ['userId'],
            ['betTime'],
            ['totalBetAmount'],
            ['IP'],
            ['txns'],
            ['refId'],
            ['betAmount']
        ];
    }
}
