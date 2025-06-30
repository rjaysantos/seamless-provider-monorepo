<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Gs5BetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE gs5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE gs5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_bet_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
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

        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/bet/?' . http_build_query($request));

        $response->assertJson([
            'status_code' => 0,
            'balance' => 90000.00
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('gs5.reports', [
            'ext_id' => 'wager-12345',
            'round_id' => '12345',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 100.00,
            'bet_valid' => 100.00,
            'bet_winlose' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);
    }

    #[DataProvider('betParams')]
    public function test_bet_incompleteRequestParameter_expectedData($parameter)
    {
        $request = [
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        unset($request[$parameter]);

        $response = $this->get(uri: 'gs5/prov/api/bet/?' . http_build_query($request));

        $response->assertJson(['status_code' => 7]);

        $response->assertStatus(200);
    }

    public static function betParams(): array
    {
        return [
            ['access_token'],
            ['txn_id'],
            ['total_bet'],
            ['game_id'],
            ['ts']
        ];
    }

    public function test_bet_tokenNotFound_expectedData()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
        ]);

        $request = [
            'access_token' => 'invalidToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/bet/?' . http_build_query($request));

        $response->assertJson(['status_code' => 1]);

        $response->assertStatus(200);
    }

    public function test_bet_transactionAlreadyExist_expectedData()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
        ]);

        DB::table('gs5.reports')->insert([
            'ext_id' => 'wager-12345',
            'round_id' => '12345',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 100.00,
            'bet_valid' => 100.00,
            'bet_winlose' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/bet/?' . http_build_query($request));

        $response->assertJson(['status_code' => 2]);

        $response->assertStatus(200);
    }

    public function test_bet_walletErrorBalance_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 999
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

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/bet/?' . http_build_query($request));

        $response->assertJson(['status_code' => 8]);

        $response->assertStatus(200);
    }

    public function test_bet_insufficientFundException_expectedData()
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

        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken',
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 100000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/bet/?' . http_build_query($request));

        $response->assertJson(['status_code' => 3]);

        $response->assertStatus(200);
    }

    public function test_bet_walletErrorWager_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
            public function Wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 999
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

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ];

        $response = $this->get(uri: 'gs5/prov/api/bet/?' . http_build_query($request));

        $response->assertJson(['status_code' => 8]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('gs5.reports', [
            'ext_id' => 'wager-12345',
            'round_id' => '12345',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_amount' => 100.00,
            'bet_valid' => 100.00,
            'bet_winlose' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);
    }
}
