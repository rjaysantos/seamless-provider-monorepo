<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use Providers\Jdb\JdbEncryption;
use Providers\Jdb\JdbCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class JdbBetTest extends TestCase
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
                    'credit_after' => 900.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 9,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '0000',
            'balance' => 900,
            'err_text' => '',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('jdb.reports', [
            'trx_id' => '123456',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null,
            'history_id' => null
        ]);
    }

    #[DataProvider('betParams')]
    public function test_bet_invalidRequest_expectedData($unset)
    {
        $payload = [
            'action' => 9,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        unset($payload[$unset]);

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '8000',
            'err_text' => 'The parameter of input error, please check your parameter is correct or not.',
        ]);

        $response->assertStatus(200);
    }

    public static function betParams()
    {
        return [
            ['action'],
            ['ts'],
            ['transferId'],
            ['uid'],
            ['currency'],
            ['amount'],
            ['gType'],
            ['mType'],
        ];
    }

    public function test_bet_playerNotFound_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 9,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'invalidPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '7501',
            'err_text' => 'User ID cannot be found.'
        ]);

        $response->assertStatus(200);
    }

    public function test_bet_transactionAlreadyExist_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('jdb.reports')->insert([
            'trx_id' => '123456',
            'bet_amount' => 100.00,
            'win_amount' => 300.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00',
            'history_id' => 'testHistoryID'
        ]);

        $payload = [
            'action' => 9,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '9011',
            'err_text' => 'Duplicate transactions.'
        ]);

        $response->assertStatus(200);
    }

    public function test_bet_insufficientFund_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 100.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 9,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 200,
            'gType' => 9,
            'mType' => 123
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '6006',
            'err_text' => 'Player balance is insufficient'
        ]);

        $response->assertStatus(200);
    }

    public function test_bet_emptyWallet_expectedData()
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
                    'status_code' => 987654321
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 9,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '9015',
            'err_text' => 'Data does not exist.'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('jdb.reports', [
            'trx_id' => '123456',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);
    }
}
