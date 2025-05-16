<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Providers\Jdb\JdbEncryption;
use Providers\Jdb\JdbCredentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class JdbCancelBetAndSettleTest extends TestCase
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

    public function test_cancelBetAndSettle_transactionNotFound_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 4,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'mType' => 1
        ];

        $request = $this->encryptData(arrayData: $payload);

        $response = $this->post('/jdb/prov/' . $payload['currency'], $request);

        $response->assertJson([
            'status' => '9017',
            'err_text' => 'Work in process, please try again later'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('cancelBetAndSettleParams')]
    public function test_cancelBetAndSettle_invalidRequest_expectedData($unset)
    {
        $payload = [
            'action' => 4,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'playID',
            'currency' => 'IDR',
        ];

        unset($payload[$unset]);

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            "status" => '8000',
            "err_text" => 'The parameter of input error, please check your parameter is correct or not.'
        ]);

        $response->assertStatus(200);
    }

    public static function cancelBetAndSettleParams()
    {
        return [
            ['action'],
            ['ts'],
            ['transferId'],
            ['uid']
        ];
    }

    public function test_cancelBetAndSettle_invalidAction_expectedData()
    {
        $payload = [
            'action' => 999,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'mType' => 1
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/' . $payload['currency'], $request);

        $response->assertJson([
            "status" => '9007',
            "err_text" => 'Unknown action.'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBetAndSettle_playerNotFound_expectedData()
    {
        DB::table('jdb.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 4,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'invalidPlayID',
            'currency' => 'IDR',
            'mType' => 1
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            "status" => '7501',
            "err_text" => 'User ID cannot be found.'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBetAndSettle_transactionAlreadyExist_expectedData()
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
            'action' => 4,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'mType' => 0
        ];

        $request = $this->encryptData($payload);

        $response = $this->post('/jdb/prov/IDR', $request);

        $response->assertJson([
            'status' => '6101',
            'err_text' => 'Can not cancel, transaction need to be settled'
        ]);

        $response->assertStatus(200);
    }
}
