<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class RedCreditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE red.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE red.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_credit_validData_expected()
    {
        $wallet = new class extends TestWallet {
            public function Payout(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
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

        DB::table('red.reports')->insert([
            'ext_id' => 'wager-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 1,
            'credit_time' => '2021-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/credit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad',
        ]);

        $response->assertJson([
            'status' => 1,
            'balance' => 900.0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('red.reports', [
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 100.0,
            'updated_at' => '2021-01-01 08:00:00',
            'created_at' => '2021-01-01 08:00:00'
        ]);
    }

    #[DataProvider('creditParams')]
    public function test_credit_incompleteRequest_expected($paramater)
    {
        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 123,
            'credit_time' => '2021-01-01 00:00:00'
        ];

        unset($request[$paramater]);

        $response = $this->post('/red/prov/credit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'MISSING_PARAMETER'
        ]);

        $response->assertStatus(200);
    }

    public static function creditParams()
    {
        return [
            ['user_id'],
            ['amount'],
            ['txn_id'],
            ['game_id'],
            ['credit_time']
        ];
    }

    public function test_credit_invalidSecretKey_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 123,
            'credit_time' => '2021-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/credit', $request, [
            'secret-key' => 'invalid secret key'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'ACCESS_DENIED'
        ]);

        $response->assertStatus(200);
    }

    public function test_credit_playerNotFound_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 98315,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 123,
            'credit_time' => '2021-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/credit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'INVALID_USER'
        ]);

        $response->assertStatus(200);
    }

    public function test_credit_transactionNotFound_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayIDu1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('red.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 100.0,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'invalidTransactionID',
            'game_id' => 123,
            'credit_time' => '2021-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/credit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'INVALID_DEBIT'
        ]);

        $response->assertStatus(200);
    }

    public function test_credit_transactionAlreadySettled_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('red.reports')->insert([
            [
                'ext_id' => 'wager-testTransactionID',
                'username' => 'testUsername',
                'play_id' => 'testPlayIDu1',
                'web_id' => 1,
                'currency' => 'IDR',
                'game_code' => 1,
                'bet_amount' => 100.0,
                'bet_winlose' => 0,
                'created_at' => '2021-01-01 00:00:00',
                'updated_at' => '2021-01-01 00:00:00',
            ],
            [
                'ext_id' => 'payout-testTransactionID',
                'username' => 'testUsername',
                'play_id' => 'testPlayIDu1',
                'web_id' => 1,
                'currency' => 'IDR',
                'game_code' => 1,
                'bet_amount' => 0,
                'bet_winlose' => 100,
                'updated_at' => '2021-01-01 00:00:00',
                'created_at' => '2021-01-01 00:00:00'
            ]
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 123,
            'credit_time' => '2021-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/credit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'DUPLICATE_CREDIT'
        ]);

        $response->assertStatus(200);
    }

    public function test_credit_invalidWalletResponse_expected()
    {
        $wallet = new class extends TestWallet {
            public function Payout(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 986516
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

        DB::table('red.reports')->insert([
            'ext_id' => 'wager-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 1,
            'credit_time' => '2021-01-01 00:00:00'
        ];

        $response = $this->post('/red/prov/credit', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'UNKNOWN_ERROR'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('red.reports', [
            'ext_id' => 'payout-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 100,
            'updated_at' => '2021-01-01 08:00:00',
            'created_at' => '2021-01-01 08:00:00'
        ]);
    }
}
