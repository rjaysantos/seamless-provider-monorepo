<?php

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;

class SboCancelTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_cancel_validRequestRunning_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Payout(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1000.00,
                    'status_code' => 2100
                ];
            }
            public function Resettle(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1100.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '1'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayerIDu027',
            'Balance' => 1100.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '1'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '0'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => 'void',
            'flag' => 'void',
            'status' => '1'
        ]);
    }

    public function test_cancel_validRequestRollback_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Resettle(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1100.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0.0,
            'result' => '-',
            'flag' => 'rollback',
            'status' => '1'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayerIDu027',
            'Balance' => 1100.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0.0,
            'result' => '-',
            'flag' => 'rollback',
            'status' => '1'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0.0,
            'result' => '-',
            'flag' => 'rollback',
            'status' => '0'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => 'void',
            'flag' => 'void',
            'status' => '1'
        ]);
    }

    public function test_cancel_validRequestSettled_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Resettle(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1100.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 300.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Over/Under',
            'sports_type' => 'Basketball',
            'event' => 'Japan B2 League',
            'match' => 'Shinshu Brave Warriors vs Yamagata Wyverns',
            'hdp' => '6.5',
            'odds' => 1.69,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayerIDu027',
            'Balance' => 1100.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 300.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Over/Under',
            'sports_type' => 'Basketball',
            'event' => 'Japan B2 League',
            'match' => 'Shinshu Brave Warriors vs Yamagata Wyverns',
            'hdp' => '6.5',
            'odds' => 1.69,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 300.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Over/Under',
            'sports_type' => 'Basketball',
            'event' => 'Japan B2 League',
            'match' => 'Shinshu Brave Warriors vs Yamagata Wyverns',
            'hdp' => '6.5',
            'odds' => 1.69,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '0'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Over/Under',
            'sports_type' => 'Basketball',
            'event' => 'Japan B2 League',
            'match' => 'Shinshu Brave Warriors vs Yamagata Wyverns',
            'hdp' => '6.5',
            'odds' => 1.69,
            'result' => 'void',
            'flag' => 'void',
            'status' => '1'
        ]);
    }

    public function test_cancel_validRequestSecondTimeVoided_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Resettle(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1100.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => 'void',
            'flag' => 'void',
            'status' => '0'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0.0,
            'result' => '-',
            'flag' => 'rollback',
            'status' => '1'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayerIDu027',
            'Balance' => 1100.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0.0,
            'result' => '-',
            'flag' => 'rollback',
            'status' => '1'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0.0,
            'result' => '-',
            'flag' => 'rollback',
            'status' => '0'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-2-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => 'void',
            'flag' => 'void',
            'status' => '1'
        ]);
    }

    #[DataProvider('cancelParams')]
    public function test_cancel_invalidRequest_expectedData($parameter)
    {
        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ];

        unset($request[$parameter]);

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);

        $response->assertStatus(200);
    }

    public static function cancelParams()
    {
        return [
            ['CompanyKey'],
            ['Username'],
            ['TransferCode']
        ];
    }

    public function test_cancel_invalidCompanyKey_expectedData()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        $request = [
            'CompanyKey' => 'invalidCompanyKey',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_playerNotFound_expectedData()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'invalidPlayer',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_transactionNotFound_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Balance(App\Contracts\V2\IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '1'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'invalidTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 6,
            'ErrorMessage' => 'Bet not exists',
            'Balance' => 1000.00,
            'AccountName' => 'testPlayerIDu027'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_transactionAlreadyVoid_expectedData()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => 'void',
            'flag' => 'void',
            'status' => '1'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 2002,
            'ErrorMessage' => 'Bet Already Cancelled'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_walletErrorPayout_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Payout(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 4035846153
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '1'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '1'
        ]);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '0'
        ]);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => 'void',
            'flag' => 'void',
            'status' => '1'
        ]);
    }

    public function test_cancel_walletErrorResettle_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Payout(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1000.00,
                    'status_code' => 2100
                ];
            }
            public function Resettle(App\Contracts\V2\IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'status_code' => 531351
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayerIDu027',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => 0,
            'ip_address' => '1.2.3.4'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '1'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '1'
        ]);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '0'
        ]);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2024-01-01 00:00:00',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => 'void',
            'flag' => 'void',
            'status' => '1'
        ]);
    }
}