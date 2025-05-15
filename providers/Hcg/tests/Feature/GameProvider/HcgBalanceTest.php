<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Providers\Hcg\HcgEncryption;
use Providers\Hcg\HcgCredentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class HcgBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hcg.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function createSignature($payload, $currency): string
    {
        $credentialsLib = new HcgCredentials();
        $credentials = $credentialsLib->getCredentialsByCurrency(currency: $currency);
        $encryptionLib = new HcgEncryption;

        return $encryptionLib->createSignature(credentials: $credentials, data: $payload);
    }

    public function test_balance_validRequest_expectedData()
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

        DB::table('hcg.players')->insert([
            'play_id' => 'playId',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $payload = [
            'action' => 1,
            'uid' => 'playId',
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post(uri: 'hcg/prov/IDR', data: $payload, headers: []);

        $response->assertJson([
            "code" => 0,
            "gold" => 1
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('currencyConversionExpectedBalance')]
    public function test_balance_validRequestCurrencyConversion_expectedData($currency, $expectedBalance)
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

        DB::table('hcg.players')->insert([
            'play_id' => 'playId',
            'username' => 'testUsername',
            'currency' => $currency,
        ]);

        $payload = [
            'action' => 1,
            'uid' => 'playId',
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: $currency);

        $response = $this->post(uri: "hcg/prov/{$currency}", data: $payload, headers: []);

        $response->assertJson([
            "code" => 0,
            "gold" => $expectedBalance
        ]);

        $response->assertStatus(200);
    }

    public static function currencyConversionExpectedBalance()
    {
        return [
            ['IDR', 1],
            ['PHP', 1000]
        ];
    }

    #[DataProvider('balanceParams')]
    public function test_balance_invalidRequest_expectedData($unset)
    {
        $payload = [
            'action' => 1,
            'uid' => 'playId',
        ];

        unset($payload[$unset]);

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        if ($unset == 'sign')
            unset($payload[$unset]);

        $response = $this->post(uri: 'hcg/prov/IDR', data: $payload, headers: []);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Validation error'
        ]);

        $response->assertStatus(200);
    }

    public static function balanceParams()
    {
        return [
            ['action'],
            ['uid'],
            ['sign']
        ];
    }

    public function test_balance_invalidSignature_expectedData()
    {
        $payload = [
            'action' => 1,
            'uid' => 'playId',
            'sign' => 'invalid signature'
        ];

        $response = $this->post(uri: 'hcg/prov/IDR', data: $payload, headers: []);

        $response->assertJson([
            'code' => '207',
            'message' => 'Sign error'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_invalidAction_expectedData()
    {
        $payload = [
            'action' => 99,
            'uid' => 'playId',
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post(uri: 'hcg/prov/IDR', data: $payload, headers: []);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Action parameter error'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_playerNotFound_expectedData()
    {
        DB::table('hcg.players')->insert([
            'play_id' => 'playId',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $payload = [
            'action' => 1,
            'uid' => 'invalidPlayID',
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post(uri: 'hcg/prov/IDR', data: $payload, headers: []);

        $response->assertJson([
            'code' => '101',
            'message' => 'User not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_emptyWallet_expectedData()
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
            'play_id' => 'playId',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $payload = [
            'action' => 1,
            'uid' => 'playId',
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post(uri: 'hcg/prov/IDR', data: $payload, headers: []);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Wallet error'
        ]);

        $response->assertStatus(200);
    }
}
