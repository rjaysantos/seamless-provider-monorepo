<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Gs5RefundTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE gs5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE gs5.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE gs5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_refund_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Cancel(IWalletCredentials $credentials, string $transactionID, float $amount, string $transactionIDToCancel): array
            {
                return [
                    'credit_after' => 3000,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('gs5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE'
        ]);

        DB::table('gs5.reports')->insert([
            'trx_id' => '123',
            'bet_amount' => 1000,
            'win_amount' => 0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '123'
        ];

        $response = $this->get(uri: 'gs5/prov/api/refund/?' . http_build_query($request));

        $response->assertJson([
            'status_code' => 0,
            'balance' => 300000.00
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('gs5.reports', [
            'trx_id' => '123',
            'bet_amount' => 1000,
            'win_amount' => 0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('gs5.reports', [
            'trx_id' => '123',
            'bet_amount' => 1000,
            'win_amount' => 1000,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);
    }

    public function test_refund_tokenNotFound_expectedData()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('gs5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE'
        ]);

        $request = [
            'access_token' => 'invalidToken',
            'txn_id' => '123'
        ];

        $response = $this->get(uri: 'gs5/prov/api/refund/?' . http_build_query($request));

        $response->assertJson(['status_code' => 1]);

        $response->assertStatus(200);
    }

    public function test_refund_transactionNotFound_expectedData()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('gs5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE'
        ]);

        DB::table('gs5.reports')->insert([
            'trx_id' => '123',
            'bet_amount' => 1000,
            'win_amount' => 0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = ['access_token' => 'testToken', 'txn_id' => 456];

        $response = $this->get(uri: 'gs5/prov/api/refund/?' . http_build_query($request));

        $response->assertJson(['status_code' => 2]);

        $response->assertStatus(200);
    }

    public function test_refund_transactionAlreadySettled_expectedData()
    {
        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('gs5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE'
        ]);

        DB::table('gs5.reports')->insert([
            'trx_id' => '123',
            'bet_amount' => 1000,
            'win_amount' => 1000,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '123'
        ];

        $response = $this->get(uri: 'gs5/prov/api/refund/?' . http_build_query($request));

        $response->assertJson(['status_code' => 2]);

        $response->assertStatus(200);
    }

    public function test_refund_walletError_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Cancel(IWalletCredentials $credentials, string $transactionID, float $amount, string $transactionIDToCancel): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('gs5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE'
        ]);

        DB::table('gs5.reports')->insert([
            'trx_id' => '123',
            'bet_amount' => 1000,
            'win_amount' => 0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = [
            'access_token' => 'testToken',
            'txn_id' => '123'
        ];

        $response = $this->get(uri: 'gs5/prov/api/refund/?' . http_build_query($request));

        $response->assertJson(['status_code' => 8]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('gs5.reports', [
            'trx_id' => '123',
            'bet_amount' => 1000,
            'win_amount' => 1000,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);
    }

    #[DataProvider('refundParams')]
    public function test_refund_invalidRequest_expectedData($unset)
    {
        $request = ['access_token' => 'invalidToken', 'txn_id' => '123'];

        unset($request[$unset]);

        $response = $this->get(uri: 'gs5/prov/api/refund/?' . http_build_query($request));

        $response->assertJson(['status_code' => 7]);

        $response->assertStatus(200);
    }

    public static function refundParams()
    {
        return [['access_token'], ['txn_id']];
    }
}