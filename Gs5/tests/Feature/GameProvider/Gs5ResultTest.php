<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Gs5ResultTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE gs5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE gs5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_result_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1300.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
        ]);

        DB::table('gs5.reports')->insert([
            'ext_id' => 'wager-123456',
            'round_id' => '123456',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 100.00,
            'bet_valid' => 100.00,
            'bet_winlose' => 0,
            'updated_at' => '2024-01-01 00:00:00',
            'created_at' => '2024-01-01 00:00:00'
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '123456',
            'total_win' => 30000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/result/?' . http_build_query($request));

        $response->assertJson([
            'status_code' => 0,
            'balance' => 130000.00
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gs5.reports', [
            'ext_id' => 'payout-123456',
            'round_id' => '123456',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 0,
            'bet_valid' => 0,
            'bet_winlose' => 200.00,
            'updated_at' => '2024-01-01 00:00:00',
            'created_at' => '2024-01-01 00:00:00'
        ]);
    }

    #[DataProvider('resultParams')]
    public function test_result_invalidRequest_expectedData($parameter)
    {
        $request = [
            'access_token' => 'testToken',
            'txn_id' => '123456',
            'total_win' => 30000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        unset($request[$parameter]);

        $response = $this->get(uri: 'gs5/prov/api/result/?' . http_build_query($request));

        $response->assertJson(['status_code' => 7]);

        $response->assertStatus(200);
    }

    public static function resultParams(): array
    {
        return [
            ['access_token'],
            ['txn_id'],
            ['total_win'],
            ['game_id'],
            ['ts']
        ];
    }

    public function test_result_tokenNotFound_expectedData()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
        ]);

        $request = [
            'access_token' => 'invalidToken',
            'txn_id' => '123456',
            'total_win' => 30000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/result/?' . http_build_query($request));

        $response->assertJson(['status_code' => 1]);

        $response->assertStatus(200);
    }

    public function test_result_transactionNotFound_expectedData()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
        ]);

        DB::table('gs5.reports')->insert([
            'ext_id' => 'wager-123456',
            'round_id' => '123456',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 0,
            'bet_valid' => 0,
            'bet_winlose' => 200.00,
            'updated_at' => '2024-01-01 00:00:00',
            'created_at' => '2024-01-01 00:00:00'
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '978456132745',
            'total_win' => 30000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/result/?' . http_build_query($request));

        $response->assertJson(['status_code' => 2]);

        $response->assertStatus(200);
    }

    public function test_result_transactionAlreadySettled_expectedData()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
        ]);

        DB::table('gs5.reports')->insert([
            'ext_id' => 'payout-123456',
            'round_id' => '123456',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 0,
            'bet_valid' => 0,
            'bet_winlose' => 200.00,
            'updated_at' => '2024-01-01 00:00:00',
            'created_at' => '2024-01-01 00:00:00'
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '123456',
            'total_win' => 30000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/result/?' . http_build_query($request));

        $response->assertJson(['status_code' => 2]);

        $response->assertStatus(200);
    }

    public function test_result_walletError_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 4553458454
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
        ]);

        DB::table('gs5.reports')->insert([
            'ext_id' => 'wager-123456',
            'round_id' => '123456',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 100.00,
            'bet_winlose' => 300.00,
            'updated_at' => '2024-01-01 00:00:00',
            'created_at' => '2024-01-01 00:00:00'
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '123456',
            'total_win' => 30000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/result/?' . http_build_query($request));

        $response->assertJson(['status_code' => 8]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('gs5.reports', [
            'ext_id' => 'payout-123456',
            'round_id' => '123456',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 0,
            'bet_valid' => 0,
            'bet_winlose' => 200.00,
            'updated_at' => '2024-01-01 00:00:00',
            'created_at' => '2024-01-01 00:00:00'
        ]);
    }
}
