<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SboDeductTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_deduct_validRequest_expected()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => '0'
        ]);

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.00,
                    'status_code' => 2100
                ];
            }

            public function Wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 900.00,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID-1',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ];

        $response = $this->post('/sbo/prov/Deduct', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 900.00,
            'BetAmount' => 100.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID-1',
            'trx_id' => 'testTransactionID-1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0,
            'bet_time' => '2021-06-01 12:23:25',
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
    }

    #[DataProvider('deductParams')]
    public function test_deduct_invalidRequestParameter_expectedData($param, $value)
    {
        $request = [
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ];

        $request[$param] = $value;
        
        $response = $this->post('/sbo/prov/Deduct', $request);

        $response->assertStatus(200);

        $response->assertJson([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);
    }

    #[DataProvider('deductParams')]
    public function test_deduct_missingRequestParameter_expectedData($param)
    {
        $request = [
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ];

        unset($request[$param]);
        
        $response = $this->post('/sbo/prov/Deduct', $request);

        $response->assertStatus(200);

        $response->assertJson([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);
    }

    public static function deductParams(): array
    {
        return [
            ['Amount', 'test'],
            ['TransferCode', 123],
            ['BetTime', 123],
            ['CompanyKey', 123],
            ['Username', 123],
            ['GameId', 'test'],
            ['ProductType', 'test']
        ];
    }

    public function test_deduct_playerNotFound_expectedData()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => '0'
        ]);

        $request = [
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'invalidPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ];

        $response = $this->post('/sbo/prov/Deduct', $request);

        $response->assertStatus(200);

        $response->assertJson([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist',
        ]);
    }

    public function test_deduct_invalidCompanyKey_expectedData()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => '0'
        ]);

        $request = [
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'invalidCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ];

        $response = $this->post('/sbo/prov/Deduct', $request);

        $response->assertStatus(200);

        $response->assertJson([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error',
        ]);
    }

    public function test_deduct_walletBalanceStatusNot2100_expecteData()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => '0'
        ]);

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ];

        $response = $this->post('/sbo/prov/Deduct', $request);

        $response->assertStatus(200);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);
    }

    public function test_deduct_balanceNotEnough_expectedData()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => '0'
        ]);

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 100.00,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'Amount' => 1000.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ];

        $response = $this->post('/sbo/prov/Deduct', $request);

        $response->assertStatus(200);

        $response->assertJson([
            'ErrorCode' => 5,
            'ErrorMessage' => 'Not enough balance',
        ]);
    }

    public function test_deduct_transactionAlreadyExists_expectedData()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => '0'
        ]);

        DB::table('sbo.reports')->insert([
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000.0,
            'payout_amount' => 0.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => '1',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => 1
        ]);

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

        $request = [
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ];

        $response = $this->post('/sbo/prov/Deduct', $request);

        $response->assertStatus(200);

        $response->assertJson([
            'ErrorCode' => 5003,
            'ErrorMessage' => 'Bet With Same RefNo Exists',
        ]);
    }

    public function test_deduct_walletWagerStatusNot2100_expectedData()
    {
        DB::table('sbo.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'game' => '0'
        ]);

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.00,
                    'status_code' => 2100
                ];
            }

            public function Wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ];

        $response = $this->post('/sbo/prov/Deduct', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0,
            'bet_time' => '2021-06-01 12:23:25',
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
    }

    public function test_deduct_NonSportProducts_expectedData()
    {
        $request = [
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 1,
            'ProductType' => 3
        ];

        $response = $this->post('/sbo/prov/Deduct', $request);
        $response->assertStatus(404);
    }
}