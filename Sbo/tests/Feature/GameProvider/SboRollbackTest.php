<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SboRollbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_rollback_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'payout-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 1200.0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 3000.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 1200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 1200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 0
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => '-',
            'flag' => 'rollback',
            'status' => 1
        ]);
    }

    public function test_rollback_validRequestAlreadyRollbackBefore_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'payout-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 1200.0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 0
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'rollback-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => '-',
                'flag' => 'rollback',
                'status' => 0
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'payout-2-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 3000.0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 3000.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'payout-2-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-2-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 0
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'rollback-2-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => '-',
            'flag' => 'rollback',
            'status' => 1
        ]);
    }

    #[DataProvider('rollbackParams')]
    public function test_rollback_incompleteRequestParameter_expectedData($param)
    {
        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        unset($request[$param]);

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);

        $response->assertStatus(200);
    }

    public static function rollbackParams()
    {
        return [
            ['CompanyKey'],
            ['Username'],
            ['TransferCode']
        ];
    }

    public function test_rollback_playerNotFound_expectedData()
    {
        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'invalidPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_invalidCompanyKey_expectedData()
    {
        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'payout-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 1200.0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'invalidCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_transactionNotFound_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2500.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'payout-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 1200.0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'invalidTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'ErrorCode' => 6,
            'ErrorMessage' => 'Bet not exists',
            'Balance' => 2500,
            'AccountName' => 'testPlayID',
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_transactionAlreadyRollback_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2500.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'rollback-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => '-',
                'flag' => 'rollback',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'ErrorCode' => 2003,
            'ErrorMessage' => 'Bet Already Rollback'
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_walletErrorBalance_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 999
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_walletErrorResettle_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'status_code' => 999
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'payout-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 1200.0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $response->assertStatus(200);
    }
}
