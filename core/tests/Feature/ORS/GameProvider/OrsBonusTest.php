<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;

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

        DB::table('ors.players')->insert([
            'play_id' => 'player_id123',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'player_id123',
            'amount' => 2000.00,
            'transaction_id' => 'transaction_id123',
            'game_code' => 123,
            'called_at' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
            'signature' => 'a2c7010a4e6796f80dbf3d6b8485f516'
        ];

        $response = $this->post('/ors/prov/api/v2/operator/transaction/reward', $request, [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_id' => 'player_id123',
            'amount' => 2000.00,
            'transaction_id' => 'transaction_id123',
            'updated_balance' => 200,
            'billing_at' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ors.reports', [
            'trx_id' => 'transaction_id123',
            'win_amount' => 2000.00,
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
    }

    public function test_reward_invalidSignature_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id123',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'player_id123',
            'amount' => 2000.00,
            'transaction_id' => 'transaction_id123',
            'game_code' => 123,
            'called_at' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
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
            'play_id' => 'player_id123',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'player_id123',
            'amount' => 2000.00,
            'transaction_id' => 'transaction_id123',
            'game_code' => 123,
            'called_at' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
            'signature' => 'a2c7010a4e6796f80dbf3d6b8485f516'
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

    /**
     * @dataProvider promotionParams
     */
    public function test_reward_invalidRequest_expectedData($param)
    {
        $request = [
            'player_id' => 'test_player_id123',
            'amount' => 2000.00,
            'transaction_id' => 'transaction_id123',
            'game_code' => 123,
            'called_at' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
            'signature' => 'a2c7010a4e6796f80dbf3d6b8485f516'
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

    public static function promotionParams()
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
            'play_id' => 'player_id123',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'invalid_play_id',
            'amount' => 2000.00,
            'transaction_id' => 'transaction_id123',
            'game_code' => 123,
            'called_at' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
            'signature' => '90e64a4bf13770041b685bebc807aab2'
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
            'play_id' => 'player_id123',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'existing_trx_id',
            'bet_amount' => 0,
            'win_amount' => 2000,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00'
        ]);

        $request = [
            'player_id' => 'player_id123',
            'amount' => 2000.00,
            'transaction_id' => 'existing_trx_id',
            'game_code' => 123,
            'called_at' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
            'signature' => '77d37a1c318ae3d68184286e7cc54672'
        ];

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
            'play_id' => 'player_id123',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'player_id' => 'player_id123',
            'amount' => 2000.00,
            'transaction_id' => 'transaction_id123',
            'game_code' => 123,
            'called_at' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
            'signature' => 'a2c7010a4e6796f80dbf3d6b8485f516'
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
            'trx_id' => 'transaction_id123',
            'win_amount' => 2000.00,
            'created_at' => '2020-01-01 00:00:00',
            'updated_at' => '2020-01-01 00:00:00',
        ]);
    }
}
