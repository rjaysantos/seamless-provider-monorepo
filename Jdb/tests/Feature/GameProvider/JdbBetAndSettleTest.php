<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use Providers\Jdb\JdbEncryption;
use Providers\Jdb\JdbCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class JdbBetAndSettleTest extends TestCase
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

    public function test_betAndSettle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.00,
                    'status_code' => 2100
                ];
            }

            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1100.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/' . $payload['currency'], $request);

        $response->assertJson([
            'status' => '0000',
            'balance' => 1100,
            'err_text' => ''
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('jdb.reports', [
            'trx_id' => '123456',
            'bet_amount' => 200,
            'win_amount' => 300,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'history_id' => 'testHistoryID'
        ]);

        Carbon::setTestNow();
    }

    #[DataProvider('betAndSettleParams')]
    public function test_betAndSettle_invalidRequest_expectedData($unset)
    {
        $payload = [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        unset($payload[$unset]);

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '8000',
            'err_text' => 'The parameter of input error, please check your parameter is correct or not.'
        ]);
    }

    public static function betAndSettleParams()
    {
        return [
            ['action'],
            ['ts'],
            ['transferId'],
            ['uid'],
            ['currency'],
            ['mType'],
            ['bet'],
            ['win']
        ];
    }

    public function test_betAndSettle_invalidAction_expectedData()
    {
        $payload = [
            'action' => 999,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'playID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/' . $payload['currency'], $request);

        $response->assertJson([
            'status' => '9007',
            'err_text' => 'Unknown action.'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_playerNotFound_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'invalidPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '7501',
            'err_text' => 'User ID cannot be found.'
        ]);
    }

    public function test_betAndSettle_transactionAlreadyExist_expectedData()
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
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '9011',
            'err_text' => 'Duplicate transactions.'
        ]);
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

        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -2000,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/' . $payload['currency'], $request);

        $response->assertJson([
            'status' => '6006',
            'err_text' => 'Player balance is insufficient'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_emptyWalletBalanceResponse_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 987645123
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
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/' . $payload['currency'], $request);

        $response->assertJson([
            'status' => '9015',
            'err_text' => 'Data does not exist.'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('jdb.reports', [
            'trx_id' => '123456',
            'bet_amount' => 200,
            'win_amount' => 300,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'history_id' => 'testHistoryID'
        ]);
    }

    public function test_betAndSettle_emptyWalletWagerAndPayoutResponse_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 987645123
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
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/' . $payload['currency'], $request);

        $response->assertJson([
            'status' => '9015',
            'err_text' => 'Data does not exist.'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('jdb.reports', [
            'trx_id' => '123456',
            'bet_amount' => 200,
            'win_amount' => 300,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'history_id' => 'testHistoryID'
        ]);
    }
}
