<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Providers\Hcg\HcgEncryption;
use Providers\Hcg\HcgCredentials;
use Illuminate\Support\Facades\DB;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class HcgBetAndSettleTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hcg.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hcg.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    private function createSignature($payload, $currency): string
    {
        $credentialsLib = new HcgCredentials;
        $credentials = $credentialsLib->getCredentialsByCurrency(currency: $currency);
        $encryptionLib = new HcgEncryption;

        return $encryptionLib->createSignature(credentials: $credentials, data: $payload);
    }

    #[DataProvider('apiEnvironment')]
    public function test_betAndSettle_validRequest_expectedData($environment, $prefix)
    {
        config(['app.env' => $environment]);

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2000.00,
                    'status_code' => 2100
                ];
            }

            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
            {
                return [
                    'credit_after' => 3000.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hcg.players')->insert([
            'play_id' => 'playID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => 0,
            'gold' => 3
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hcg.reports', [
            'trx_id' => "{$prefix}-transactionID",
            'bet_amount' => 1000,
            'win_amount' => 3000,
            'created_at' => '2024-08-14 14:47:42',
            'updated_at' => '2024-08-14 14:47:42'
        ]);
    }

    public static function apiEnvironment()
    {
        return [
            ['STAGING', '0'],
            ['PRODUCTION', '1']
        ];
    }

    #[DataProvider('currencyConversionExpectedData')]
    public function test_betAndSettle_validRequestCurrencyConversion_expectedData($currency, $bet, $win, $expectedBalance)
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2000.00,
                    'status_code' => 2100
                ];
            }

            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
            {
                return [
                    'credit_after' => 4000.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hcg.players')->insert([
            'play_id' => 'playID',
            'username' => 'testUsername',
            'currency' => $currency
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => $bet,
            'win' => $win,
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post('/hcg/prov/{$currency}', $payload);

        $response->assertJson([
            'code' => 0,
            'gold' => $expectedBalance
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hcg.reports', [
            'trx_id' => '0-transactionID',
            'bet_amount' => 1000,
            'win_amount' => 3000,
            'created_at' => '2024-08-14 14:47:42',
            'updated_at' => '2024-08-14 14:47:42'
        ]);
    }

    public static function currencyConversionExpectedData()
    {
        return [
            ['IDR', 1, 3, 4],
            ['PHP', 1000, 3000, 4000],
        ];
    }

    #[DataProvider('betAndSettleParams')]
    public function test_betAndSettle_invalidRequest_expectedData($unset)
    {
        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        unset($payload[$unset]);

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        if ($unset == 'sign')
            unset($payload[$unset]);

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Validation error'
        ]);

        $response->assertStatus(200);
    }

    public static function betAndSettleParams()
    {
        return [
            ['action'],
            ['uid'],
            ['timestamp'],
            ['orderNo'],
            ['gameCode'],
            ['bet'],
            ['win'],
            ['sign']
        ];
    }

    public function test_betAndSettle_invalidSignature_expectedData()
    {
        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $payload['sign'] = 'invalid Signature';

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '207',
            'message' => 'Sign error'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_invalidAction_expectedData()
    {
        $payload = [
            'action' => 999,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Action parameter error'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_playerNotFound_expectedData()
    {
        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '101',
            'message' => 'User not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_transactionAlreadyExist_expectedData()
    {
        DB::table('hcg.players')->insert([
            'play_id' => 'playID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hcg.reports')->insert([
            'trx_id' => '0-transactionID',
            'bet_amount' => 2000,
            'win_amount' => 3000,
            'created_at' => '2024-08-14 14:47:42',
            'updated_at' => '2024-08-14 14:47:42'
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '102',
            'message' => 'Duplicate order number'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_insufficientFund_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hcg.players')->insert([
            'play_id' => 'playID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '106',
            'message' => 'Balance is not enough'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_emptyWalletBalance_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 9999
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hcg.players')->insert([
            'play_id' => 'playID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Wallet error'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_emptyWalletWagerAndPayout_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2000.00,
                    'status_code' => 2100
                ];
            }

            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
            {
                return [
                    'status_code' => 9999
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hcg.players')->insert([
            'play_id' => 'playID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => 'transactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Wallet error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hcg.reports', [
            'trx_id' => '0-transactionID',
            'bet_amount' => 1000,
            'win_amount' => 3000,
            'created_at' => '2024-08-14 14:47:42',
            'updated_at' => '2024-08-14 14:47:42'
        ]);
    }
}