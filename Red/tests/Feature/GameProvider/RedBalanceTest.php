<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class RedBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE red.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE red.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_balance_validRequest_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'prd_id' => 1,
        ];

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

        $response = $this->post('/red/prov/balance', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 1,
            'balance' => 1000.00
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('balanceParameters')]
    public function test_balance_incompleteParameter_expected($param)
    {
        $request = [
            'user_id' => 3,
            'prd_id' => 1,
        ];

        unset($request[$param]);

        $response = $this->post('/red/prov/balance', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'MISSING_PARAMETER'
        ]);

        $response->assertStatus(200);
    }

    public static function balanceParameters()
    {
        return [
            ['user_id'],
            ['prd_id'],
        ];
    }

    public function test_balance_invalidSecretKey_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'prd_id' => 1,
        ];

        $response = $this->post('/red/prov/balance', $request, [
            'secret-key' => 'invalid_secret_key'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'ACCESS_DENIED'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_playerNotFound_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 9999,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 1234567,
            'prd_id' => 1,
        ];

        $response = $this->post('/red/prov/balance', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'INVALID_USER'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_invalidWalletResponse_expected()
    {
        DB::table('red.players')->insert([
            'user_id_provider' => 27,
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'user_id' => 27,
            'prd_id' => 1,
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 987654132
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('/red/prov/balance', $request, [
            'secret-key' => 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad'
        ]);

        $response->assertJson([
            'status' => 0,
            'error' => 'UNKNOWN_ERROR'
        ]);

        $response->assertStatus(200);
    }
}
