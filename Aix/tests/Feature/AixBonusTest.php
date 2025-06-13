<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class AixBonusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE aix.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE aix.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_bonus_validRequest_expectedData()
    {
        DB::table('aix.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('aix.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200.0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        Carbon::setTestNow('2025-01-01 00:00:00');

        $request = [
            'user_id' => 'testPlayIDu001',
            'amount' => 100.0,
            'prd_id' => 1,
            'txn_id' => 'testTransactionID',
        ];

        $wallet = new class extends TestWallet {
            public function bonus(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1300.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('/aix/prov/bonus', $request, [
            'secret-key' => 'ais-secret-key'
        ]);

        $response->assertJson([
            'balance' => 1300.0,
            'status' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('aix.reports', [
            'ext_id' => 'bonus-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 100.0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);
    }

    #[DataProvider('bonusParams')]
    public function test_bonus_missingRequest_expectedData($parameter)
    {
        $request = [
            'user_id' => 'testPlayIDu001',
            'amount' => 100.0,
            'prd_id' => 1,
            'txn_id' => 'testTransactionID',
        ];

        unset($request[$parameter]);

        $response = $this->post('/aix/prov/bonus', $request, [
            'secret-key' => 'ais-secret-key'
        ]);

        $response->assertJson(['status' => 0]);

        $response->assertStatus(200);
    }

    #[DataProvider('bonusParams')]
    public function test_bonus_invalidRequestType_expectedData($parameter, $data)
    {
        $request = [
            'user_id' => 'testPlayIDu001',
            'amount' => 100.0,
            'prd_id' => 1,
            'txn_id' => 'testTransactionID',
        ];

        $request[$parameter] = $data;

        $response = $this->post('/aix/prov/bonus', $request, [
            'secret-key' => 'ais-secret-key'
        ]);

        $response->assertJson(['status' => 0]);

        $response->assertStatus(200);
    }

    public static function bonusParams()
    {
        return [
            ['user_id', 123],
            ['amount', 'test'],
            ['prd_id', 'test'],
            ['txn_id', 123]
        ];
    }

    public function test_bonus_playerNotFound_expectedData()
    {
        DB::table('aix.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 'invalidPlayerID',
            'amount' => 100.0,
            'prd_id' => 1,
            'txn_id' => 'testTransactionID',
        ];

        $response = $this->post('/aix/prov/bonus', $request, [
            'secret-key' => 'ais-secret-key'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'INVALID_USER'
        ]);

        $response->assertStatus(200);
    }

    public function test_bonus_invalidSecretKey_expectedData()
    {
        DB::table('aix.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 'testPlayIDu001',
            'amount' => 100.0,
            'prd_id' => 1,
            'txn_id' => 'testTransactionID',
        ];

        $response = $this->post('/aix/prov/bonus', $request, [
            'secret-key' => 'invalidSecretKey'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'ACCESS_DENIED'
        ]);

        $response->assertStatus(200);
    }

    public function test_bonus_transactionHasBonus_expectedData()
    {
        DB::table('aix.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('aix.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 200.0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        DB::table('aix.reports')->insert([
            'ext_id' => 'bonus-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 100.0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'user_id' => 'testPlayIDu001',
            'amount' => 100.0,
            'prd_id' => 1,
            'txn_id' => 'testTransactionID',
        ];

        $response = $this->post('/aix/prov/bonus', $request, [
            'secret-key' => 'ais-secret-key'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'DUPLICATE_CREDIT'
        ]);

        $response->assertStatus(200);
    }

    public function test_bonus_wagerWalletResponseCodeNot2100_expectedData()
    {
        DB::table('aix.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('aix.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            'updated_at' => '2025-01-01 00:00:00',
            'created_at' => '2025-01-01 00:00:00'
        ]);

        Carbon::setTestNow('2025-01-01 00:00:00');

        $request = [
            'user_id' => 'testPlayIDu001',
            'amount' => 100.0,
            'prd_id' => 1,
            'txn_id' => 'testTransactionID',
        ];

        $wallet = new class extends TestWallet {
            public function bonus(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('/aix/prov/bonus', $request, [
            'secret-key' => 'ais-secret-key'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'UNKNOWN_ERROR'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('aix.reports', [
            'ext_id' => 'bonus-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 100.0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);
    }
}
