<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use App\GameProviders\V2\Jdb\JdbEncryption;
use App\GameProviders\V2\Jdb\JdbCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class JdbCancelBetTest extends TestCase
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

    public function test_cancelBet_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Cancel(IWalletCredentials $credentials, string $transactionID, float $amount, string $transactionIDToCancel): array
            {
                return [
                    'credit_after' => 1000.0,
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

        DB::table('jdb.reports')->insert([
            'trx_id' => '123456',
            'bet_amount' => 100.00,
            'win_amount' => 0.00,
            'updated_at' => null,
            'created_at' => '2021-01-01 00:00:00',
            'history_id' => null
        ]);

        $payload = [
            'action' => 11,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '0000',
            'balance' => 1000,
            'err_text' => '',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('jdb.reports', [
            'trx_id' => '123456',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'history_id' => null
        ]);
    }

    #[DataProvider('cancelBetParams')]
    public function test_cancelBet_invalidRequest_expectedData($unset)
    {
        $payload = [
            'action' => 11,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        unset($payload[$unset]);

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '8000',
            'err_text' => 'The parameter of input error, please check your parameter is correct or not.'
        ]);

        $response->assertStatus(200);
    }

    public static function cancelBetParams()
    {
        return [
            ['action'],
            ['ts'],
            ['uid'],
            ['currency'],
            ['amount'],
            ['refTransferIds'],
        ];
    }

    public function test_cancelBet_playerNotFound_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 11,
            'ts' => 1609430400000,
            'uid' => 'invalidPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '7501',
            'err_text' => 'User ID cannot be found.'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_transactionNotFound_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('jdb.reports')->insert([
            'trx_id' => '123456',
            'bet_amount' => 100.00,
            'win_amount' => 0.00,
            'updated_at' => null,
            'created_at' => '2021-01-01 00:00:00',
            'history_id' => null
        ]);

        $payload = [
            'action' => 11,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [3126548]
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '9999',
            'err_text' => 'Failed'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_transactionAlreadySettled_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
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

        DB::table('jdb.reports')->insert([
            'trx_id' => '123456',
            'bet_amount' => 100.00,
            'win_amount' => 100.00,
            'updated_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00',
            'history_id' => '053684513'
        ]);

        $payload = [
            'action' => 11,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '0000',
            'balance' => 1000,
            'err_text' => '',
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_emptyWallet_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Cancel(IWalletCredentials $credentials, string $transactionID, float $amount, string $transactionIDToCancel): array
            {
                return [
                    'status_code' => 65456153
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('jdb.reports')->insert([
            'trx_id' => '123456',
            'bet_amount' => 100.00,
            'win_amount' => 0.00,
            'updated_at' => null,
            'created_at' => '2021-01-01 00:00:00',
            'history_id' => null
        ]);

        $payload = [
            'action' => 11,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $request = $this->encryptData(arrayData: $payload);

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
            'updated_at' => '2021-01-01 00:00:00',
            'history_id' => null
        ]);
    }
}
