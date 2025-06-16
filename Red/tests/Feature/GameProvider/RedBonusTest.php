<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class RedBonusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE red.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE red.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_bonus_validRequest_expectedData()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 1
        ];

        $wallet = new class extends TestWallet {
            public function Bonus(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 200.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        Carbon::setTestNow('2025-01-01 00:00:00');

        $response = $this->post('/red/prov/bonus', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'balance' => 200.00,
            'status' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('red.reports', [
            'ext_id' => 'bonus-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 200.0,
            'created_at' => '2025-01-01 08:00:00',
            'updated_at' => '2025-01-01 08:00:00'
        ]);
    }

    #[DataProvider('bonusParams')]
    public function test_bonus_incompleteRequest_expectedData($param)
    {
        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 51
        ];

        unset($request[$param]);

        $response = $this->post('/red/prov/bonus', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'MISSING_PARAMETER'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('bonusParams')]
    public function test_bonus_invalidRequestParameters_expectedData($param, $value)
    {
        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 51
        ];

        $request[$param] = $value;

        $response = $this->post('/red/prov/bonus', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'MISSING_PARAMETER'
        ]);

        $response->assertStatus(200);
    }

    public static function bonusParams()
    {
        return [
            ['user_id', 'test'],
            ['amount', 'test'],
            ['txn_id', 123],
            ['game_id', 'test']
        ];
    }

    public function test_bonus_invalidSecretKey_expectedData()
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
            'game_id' => 51
        ];

        $response = $this->post('/red/prov/bonus', $request, [
            'secret-key' => 'invalid secret key'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'ACCESS_DENIED'
        ]);

        $response->assertStatus(200);
    }

    public function test_bonus_transactionAlreadyExists_expectedData()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('red.reports')->insert([
            'ext_id' => 'bonus-testTransactionID',
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

        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 51
        ];

        $response = $this->post('/red/prov/bonus', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'error' => 'DUPLICATE_BONUS',
            'status' => 0
        ]);

        $response->assertStatus(200);
    }

    public function test_bonus_invalidWalletResponse_expectedData()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'amount' => 200.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 51
        ];

        $wallet = new class extends TestWallet {
            public function Bonus(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 64286482
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Carbon::setTestNow('2025-01-01 00:00:00');

        $response = $this->post('/red/prov/bonus', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'error' => 'UNKNOWN_ERROR',
            'status' => 0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('red.reports', [
            'ext_id' => 'bonus-testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 200.0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);

        Carbon::setTestNow();
    }
}
