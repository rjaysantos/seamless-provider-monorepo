<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use App\GameProviders\V2\Jdb\JdbEncryption;
use App\GameProviders\V2\Jdb\JdbCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class JdbBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE jdb.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE jdb.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE jdb.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function encryptData(array $arrayData): array
    {
        $credentialsLib = new JdbCredentials();
        $credentials = $credentialsLib->getCredentialsByCurrency(currency: $arrayData['currency'] ?? 'IDR');
        $encryptionLib = new JdbEncryption();
        $encryptedData = $encryptionLib->encrypt(credentials: $credentials, data: $arrayData);

        return ['x' => $encryptedData];
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

        DB::table('jdb.players')->insert([
            'play_id' => 'playId',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 6,
            'uid' => 'playId',
            'currency' => 'IDR'
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '0000',
            'balance' => 1000,
            'err_text' => ''
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('balanceParams')]
    public function test_balance_invalidRequest_expectedData($param)
    {
        $payload = [
            'action' => 6,
            'uid' => 'playId',
            'currency' => 'IDR'
        ];

        unset($payload[$param]);

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '8000',
            'err_text' => 'The parameter of input error, please check your parameter is correct or not.'
        ]);

        $response->assertStatus(200);
    }

    public static function balanceParams()
    {
        return [
            ['action'],
            ['uid'],
            ['currency']
        ];
    }

    public function test_balance_invalidAction_expectedData()
    {
        $payload = [
            'action' => 999,
            'uid' => 'playId',
            'currency' => 'IDR'
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '9007',
            'err_text' => 'Unknown action.'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_playerNotFound_expectedData()
    {
        $payload = [
            'action' => 6,
            'uid' => 'playId',
            'currency' => 'IDR'
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '7501',
            'err_text' => 'User ID cannot be found.'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_emptyWallet_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 54743
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('jdb.players')->insert([
            'play_id' => 'playId',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 6,
            'uid' => 'playId',
            'currency' => 'IDR'
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '9015',
            'err_text' => 'Data does not exist.'
        ]);

        $response->assertStatus(200);
    }
}
