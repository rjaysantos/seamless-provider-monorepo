<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SabAdjustBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_adjustBalance_validRequestCredit_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function TransferIn(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
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
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
            'ip_address' => null
        ]);
    }

    public function test_adjustBalance_validRequestDebit_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function TransferOut(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
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
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 3,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => -3000.0,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
            'ip_address' => null
        ]);
    }

    #[DataProvider('currencyConversionParams')]
    public function test_adjustBalance_validDataCurrencyConversionCredit_expectedData($currency, $convertedAmount)
    {
        $wallet = new class extends TestWallet {
            public function TransferIn(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
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
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 0,
            'payout_amount' => $convertedAmount,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
        ]);
    }

    #[DataProvider('currencyConversionParams')]
    public function test_adjustBalance_validDataCurrencyConversionDebit_expectedData($currency, $convertedAmount)
    {
        $wallet = new class extends TestWallet {
            public function TransferOut(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
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
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 1,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 0,
            'payout_amount' => -$convertedAmount,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
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

    public function test_adjustBalance_playerNotFound_expectedData()
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
                'operationId' => 'testOperationId',
                'userId' => 'invalidUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 1,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_adjustBalance_invalidKey_expectedData()
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
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 1,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_adjustBalance_transactionAlreadyExists_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function TransferIn(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
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
                'bet_id' => 'testOperationId-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 0,
                'payout_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'bet_choice' => '-',
                'game_code' => 17003,
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'bonus',
                'status' => 1,
                'ip_address' => null
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_adjustBalance_invalidWalletTransferInResponse_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function TransferIn(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
            'ip_address' => null
        ]);
    }

    public function test_adjustBalance_invalidWalletTransferOutResponse_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function TransferOut(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 1,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => -1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
            'ip_address' => null
        ]);
    }

    #[DataProvider('adjustBalanceParams')]
    public function test_adjustBalance_incompleteRequest_expectedData($unset)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        if (isset($request[$unset]) === true)
            unset($request[$unset]);
        else if (isset($request['message'][$unset]) === true)
            unset($request['message'][$unset]);
        else
            unset($request['message']['balanceInfo'][$unset]);

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function adjustBalanceParams()
    {
        return [
            ['key'],
            ['message'],
            ['operationId'],
            ['userId'],
            ['txId'],
            ['time'],
            ['betType'],
            ['balanceInfo'],
            ['creditAmount'],
            ['debitAmount'],
        ];
    }
}