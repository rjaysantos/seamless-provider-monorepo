<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class PlaGetBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pla.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_getBalance_validRequest_expectedData()
    {
        DB::table('pla.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR',
            'token' => 'PLAUC_TOKEN123456789'
        ]);

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        $payload = [
            'requestId' => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        app()->bind(IWallet::class, $wallet::class);

        Carbon::setTestNow('2024-04-07 00:00:00');

        $response = $this->post('pla/prov/getbalance', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            "balance" => [
                "real" => "1000.00",
                "timestamp" => "2024-04-06 16:00:00.000"
            ]
        ]);

        Carbon::setTestNow();
    }

    #[DataProvider('getBalanceParams')]
    public function test_getBalance_invalidRequest_expectedData($unset, $token)
    {
        $payload = [
            'requestId' => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        unset($payload[$unset]);

        $response = $this->post('pla/prov/getbalance', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            "requestId" => $token,
            "error" => [
                "code" => "CONSTRAINT_VIOLATION"
            ]
        ]);
    }

    public static function getBalanceParams()
    {
        return [
            ['requestId', ''],
            ['username', '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9'],
            ['externalToken', '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9']
        ];
    }

    public function test_getBalance_playerNotFound_expectedData()
    {
        $payload = [
            'requestId' => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        $response = $this->post('pla/prov/getbalance', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            "requestId" => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            "error" => [
                "code" => "ERR_PLAYER_NOT_FOUND"
            ]
        ]);
    }

    public function test_getBalance_usernameWithoutKiosk_expectedData()
    {
        $payload = [
            'requestId' => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            'username' => 'invalidUsername',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        $response = $this->post('pla/prov/getbalance', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            "requestId" => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            "error" => [
                "code" => "ERR_PLAYER_NOT_FOUND"
            ]
        ]);
    }

    public function test_getBalance_invalidToken_expectedData()
    {
        DB::table('pla.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR',
            'token' => 'PLAUC_TOKEN123456789'
        ]);

        $payload = [
            'requestId' => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'INVALID_TOKEN'
        ];

        $response = $this->post('pla/prov/getbalance', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            "requestId" => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            "error" => [
                "code" => "ERR_AUTHENTICATION_FAILED"
            ]
        ]);
    }

    public function test_getBalance_invalidWalletResponse_expectedData()
    {
        DB::table('pla.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR',
            'token' => 'PLAUC_TOKEN123456789'
        ]);


        $payload = [
            'requestId' => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Carbon::setTestNow('2024-04-07 00:00:00');

        $response = $this->post('pla/prov/getbalance', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            "requestId" => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            "error" => [
                "code" => "INTERNAL_ERROR"
            ]
        ]);

        Carbon::setTestNow();
    }

    #[DataProvider('walletAndExpectedAmount')]
    public function test_getBalance_validDataGivenBalance_expectedData($wallet, $expectedBalance)
    {
        DB::table('pla.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR',
            'token' => 'PLAUC_TOKEN123456789'
        ]);

        $payload = [
            'requestId' => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        app()->bind(IWallet::class, $wallet::class);

        Carbon::setTestNow('2024-04-07 00:00:00');

        $response = $this->post('pla/prov/getbalance', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => '71c8ebdd-5cbe-4294-bf40-ae12b93fdef9',
            "balance" => [
                "real" => $expectedBalance,
                "timestamp" => "2024-04-06 16:00:00.000"
            ]
        ]);

        Carbon::setTestNow();
    }

    public static function walletAndExpectedAmount()
    {
        return [
            [new class extends TestWallet {
                public function balance(IWalletCredentials $credentials, string $playID): array
                {
                    return ['credit' => 123, 'status_code' => 2100];
                }
            }, '123.00'],
            
            [new class extends TestWallet {
                public function balance(IWalletCredentials $credentials, string $playID): array
                {
                    return ['credit' => 123.456789, 'status_code' => 2100];
                }
            }, '123.45'],
            
            [new class extends TestWallet {
                public function balance(IWalletCredentials $credentials, string $playID): array
                {
                    return ['credit' => 123.409987, 'status_code' => 2100];
                }
            }, '123.40'],
            
            [new class extends TestWallet {
                public function balance(IWalletCredentials $credentials, string $playID): array
                {
                    return ['credit' => 123.000, 'status_code' => 2100];
                }
            }, '123.00'],
            
            [new class extends TestWallet {
                public function balance(IWalletCredentials $credentials, string $playID): array
                {
                    return ['credit' => 123.000009, 'status_code' => 2100];
                }
            }, '123.00'],
            
            [new class extends TestWallet {
                public function balance(IWalletCredentials $credentials, string $playID): array
                {
                    return ['credit' => 100.000, 'status_code' => 2100];
                }
            }, '100.00'],
            
            [new class extends TestWallet {
                public function balance(IWalletCredentials $credentials, string $playID): array
                {
                    return ['credit' => 100, 'status_code' => 2100];
                }
            }, '100.00'],
        ];
    }
}