<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class RedDebitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE red.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE red.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_debit_validRequest_expected()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.00,
                    'status_code' => 2100
                ];
            }

            public function Wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 900.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayIDu1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 100.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 2,
            'debit_time' => '2020-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/debit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'balance' => 900.00,
            'status' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('red.reports', [
            'ext_id' => 'wager-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '2',
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            'updated_at' => '2020-01-01 08:00:00',
            'created_at' => '2020-01-01 08:00:00'
        ]);
    }

    #[DataProvider('debitParams')]
    public function test_debit_invalidRequest_expected($param)
    {
        $request = [
            'user_id' => 27,
            'amount' => 100.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 2,
            'debit_time' => '2020-01-01 00:00:00'
        ];

        unset($request[$param]);

        $response = $this->post('/red/prov/debit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'MISSING_PARAMETER'
        ]);

        $response->assertStatus(200);
    }

    public static function debitParams()
    {
        return [
            ['user_id'],
            ['amount'],
            ['txn_id'],
            ['game_id'],
            ['debit_time']
        ];
    }

    public function test_debit_invalidSecretKey_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 100.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 2,
            'debit_time' => '2020-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/debit', $request, [
            'secret-key' => 'invalid secret key'
        ]);

        $response->assertJson([
            'error' => 'ACCESS_DENIED',
            'status' => 0
        ]);

        $response->assertStatus(200);
    }

    public function test_debit_playerNotFound_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 7894653,
            'amount' => 100.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 2,
            'debit_time' => '2020-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/debit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'error' => 'INVALID_USER',
            'status' => 0
        ]);

        $response->assertStatus(200);
    }

    public function test_debit_transactionAlreadyExist_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayIDu1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('red.reports')->insert([
            'ext_id' => 'wager-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 100.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 2,
            'debit_time' => '2025-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/debit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'error' => 'DUPLICATE_DEBIT',
            'status' => 0
        ]);

        $response->assertStatus(200);
    }

    public function test_debit_insufficientFunds_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 10000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 2,
            'debit_time' => '2020-01-01 00:00:00'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 100.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('/red/prov/debit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'INSUFFICIENT_FUNDS'
        ]);

        $response->assertStatus(200);
    }

    public function test_debit_invalidWalletResponse_expected()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.00,
                    'status_code' => 2100
                ];
            }

            public function Wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 3216549
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayIDu1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 100.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 2,
            'debit_time' => '2020-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/debit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'error' => 'UNKNOWN_ERROR',
            'status' => 0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('red.reports', [
            'ext_id' => 'wager-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '2',
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            'updated_at' => '2020-01-01 08:00:00',
            'created_at' => '2020-01-01 08:00:00'
        ]);
    }
}
