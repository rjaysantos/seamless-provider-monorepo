<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class OrsBonusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ors.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ors.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_reward_validRequest_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'testPlayeru001',
            'amount' => 2000.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => Carbon::parse('2025-01-01 00:00:00')->timestamp,
            'signature' => '0c3841d9aa665d66f95519b04747fa44'
        ];

        $wallet = new class extends TestWallet {
            public function Bonus(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $transactionID,
                float $amount,
                Report $report
            ): array {
                return [
                    'credit_after' => 200.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('/ors/prov/api/v2/operator/transaction/reward', $request, [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_id' => 'testPlayeru001',
            'amount' => 2000.00,
            'transaction_id' => 'testTransactionID',
            'updated_balance' => 200,
            'billing_at' => Carbon::parse('2025-01-01 00:00:00')->timestamp,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ors.reports', [
            'ext_id' => 'bonus-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '123',
            'bet_amount' => 0,
            'bet_winlose' => 2000.0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);
    }

    public function test_reward_invalidSignature_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'testPlayeru001',
            'amount' => 2000.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => Carbon::parse('2025-01-01 00:00:00')->timestamp,
            'signature' => 'invalid_signature'
        ];

        $response = $this->post('/ors/prov/api/v2/operator/transaction/reward?', $request, [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'E-103',
            'rs_message' => 'invalid signature',
        ]);

        $response->assertStatus(200);
    }

    public function test_reward_invalidPublicKeyHeader_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'testPlayeru001',
            'amount' => 2000.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => Carbon::parse('2025-01-01 00:00:00')->timestamp,
            'signature' => 'invalid_signature'
        ];

        $response = $this->post('/ors/prov/api/v2/operator/transaction/reward?', $request, [
            'key' => 'invalid_key'
        ]);

        $response->assertJson([
            'rs_code' => 'E-102',
            'rs_message' => 'invalid public key in header'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('bonusParams')]
    public function test_reward_invalidRequest_expectedData($param)
    {
        $request = [
            'player_id' => 'testPlayeru001',
            'amount' => 2000.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => Carbon::parse('2025-01-01 00:00:00')->timestamp,
            'signature' => '0c3841d9aa665d66f95519b04747fa44'
        ];

        unset($request[$param]);

        $response = $this->post('/ors/prov/api/v2/operator/transaction/reward?', $request, [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'E-104',
            'rs_message' => 'invalid parameter',
        ]);

        $response->assertStatus(200);
    }

    public static function bonusParams()
    {
        return [
            ['player_id'],
            ['amount'],
            ['transaction_id'],
            ['game_code'],
            ['called_at'],
            ['signature'],
        ];
    }

    public function test_reward_playerNotFound_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayeru002',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'testPlayeru001',
            'amount' => 2000.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => Carbon::parse('2025-01-01 00:00:00')->timestamp,
            'signature' => '0c3841d9aa665d66f95519b04747fa44'
        ];

        $response = $this->post('/ors/prov/api/v2/operator/transaction/reward?', $request, [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'S-104',
            'rs_message' => 'player not available',
        ]);

        $response->assertStatus(200);
    }

    public function test_reward_transactionAlreadyExist_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'testPlayeru001',
            'amount' => 2000.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => Carbon::parse('2025-01-01 00:00:00')->timestamp,
            'signature' => '0c3841d9aa665d66f95519b04747fa44'
        ];

        DB::table('ors.reports')->insert([
            'ext_id' => 'bonus-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 2000.0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);

        $response = $this->post('/ors/prov/api/v2/operator/transaction/reward?', $request, [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'S-101',
            'rs_message' => 'transaction is duplicated',
        ]);

        $response->assertStatus(200);
    }

    public function test_reward_invalidWalletResponse_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Bonus(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $transactionID,
                float $amount,
                Report $report
            ): array {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('ors.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'testPlayeru001',
            'amount' => 2000.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => Carbon::parse('2025-01-01 00:00:00')->timestamp,
            'signature' => '0c3841d9aa665d66f95519b04747fa44'
        ];

        $response = $this->post('/ors/prov/api/v2/operator/transaction/reward?', $request, [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'S-113',
            'rs_message' => 'internal error on the operator',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('ors.reports', [
            'ext_id' => 'bonus-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_amount' => 0,
            'bet_winlose' => 200.0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);
    }
}
