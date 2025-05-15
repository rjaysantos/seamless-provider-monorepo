<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SabBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_balance_validData_balanceResponse()
    {
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ];

        $response = $this->post('/sab/prov/getbalance', $request);

        $response->assertJson([
            'status' => 0,
            'userId'  => 'test-player-username',
            'balance' => 1,
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('currencyConversionParams')]
    public function test_balance_validDataCurrencyConversion_balanceResponse($currency, $value)
    {
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => $currency,
                'game' => '0',
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ];

        $response = $this->post('/sab/prov/getbalance', $request);

        $response->assertJson([
            'status' => 0,
            'userId'  => 'test-player-username',
            'balance' => $value,
        ]);

        $response->assertStatus(200);
    }

    public static function currencyConversionParams()
    {
        return [
            ['IDR', 1],
            ['THB', 1000],
            ['VND', 1],
            ['BRL', 1000],
            ['USD', 1000]
        ];
    }

    public function test_balance_invalidKey_invalidKeyResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => 'invalid_vendor_id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ];

        $response = $this->post('/sab/prov/getbalance', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_invalidUserId_playerNotFoundResponse()
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'invalid-player-username',
            ]
        ];

        $response = $this->post('/sab/prov/getbalance', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_walletError_walletErrorResponse()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ];

        $response = $this->post('/sab/prov/getbalance', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('balanceParams')]
    public function test_balance_incompleteRequestParameters_invalidRequestResponse($key)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        else
            unset($request['message'][$key]);

        $response = $this->post('/sab/prov/getbalance', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function balanceParams()
    {
        return [
            ['key'],
            ['message'],
            ['userId']
        ];
    }
}
