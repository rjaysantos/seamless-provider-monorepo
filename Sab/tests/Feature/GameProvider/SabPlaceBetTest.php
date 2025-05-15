<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SabPlaceBetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_placeBet_validData_placeBetResponse()
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
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
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
            'game_code' => 1,
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
    public function test_placeBet_validDataCurrencyConversion_placeBetResponse($currency, $value)
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
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
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
            'game_code' => 1,
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

    public function test_placeBet_invalidKey_invalidKeyResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => 'invalid_vendor_id',
            'message' => [
                'operationId' => 'testOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBet_transactionAlreadyExists_transactionAlreadyExistResponse()
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
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
                'game_code' => '1',
                'sports_type' => 'Soccer',
                'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                'match' => 'Netherlands vs Portugal',
                'hdp' => 3.4,
                'odds' => 1.24,
                'result' => 'won',
                'flag' => 'settled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 1,
            'msg' => 'Duplicate Transaction'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBet_insufficientBalance_insufficientFundResponse()
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
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

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

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 502,
            'msg' => 'Player Has Insufficient Funds'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBet_invalidUserId_playerNotFoundResponse()
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
                'refId' => 'testTransactionID',
                'userId' => 'non-existent-username',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBet_walletError_walletErrorResponse()
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
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('placeBetParams')]
    public function test_placeBet_incompleteRequestParameter_invalidRequestResponse($key)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        else
            unset($request['message'][$key]);

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function placeBetParams()
    {
        return [
            ['key'],
            ['message'],
            ['operationId'],
            ['refId'],
            ['userId'],
            ['betTime'],
            ['actualAmount'],
            ['betType'],
            ['IP']
        ];
    }
}