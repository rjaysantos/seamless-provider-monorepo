<?php

use Tests\TestCase;
use App\Models\SboPlayer;
use App\Models\SboReport;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use App\Contracts\IWallet;

class SboStatusTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    /**
     * @dataProvider multipleStatus
     */
    public function test_status_validRequestMultipleStatus_expected($status, $rollback = null)
    {
        SboPlayer::factory()->create([
            'play_id' => 'player_id'
        ]);

        SboReport::factory()->create([
            'trx_id' => '3998211',
            'bet_amount' => 100.00,
            'payout_amount' => 150.00,
            'flag' => $status,
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'player_id',
            'TransferCode' => '3998211'
        ];

        $response = $this->post('/sbo/prov/GetBetStatus', $request);

        $response->assertJson([
            'TransferCode' => '3998211',
            'TransactionId' => '3998211',
            'Status' => is_null($rollback) ? $status : $rollback,
            'WinLoss' => '150.00',
            'Stake' => '100.00',
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);
    }

    public static function multipleStatus()
    {
        return [
            ['running'],
            ['settled'],
            ['void'],
            ['rollback', 'running'],
            ['running-inc', 'running'],
        ];
    }

    /**
     * @dataProvider productionParams
     */
    public function test_status_validRequestMultipleCurrency_expectedData($currency, $companyKey)
    {
        config(['app.env' => 'PRODUCTION']);

        SboPlayer::factory()->create([
            'play_id' => 'player_id',
            'currency' => $currency
        ]);

        SboReport::factory()->create([
            'trx_id' => '3998211',
            'bet_amount' => 100.00,
            'payout_amount' => 150.00,
            'currency' => $currency,
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => $companyKey,
            'Username' => 'player_id',
            'TransferCode' => '3998211'
        ];

        $response = $this->post('/sbo/prov/GetBetStatus', $request);

        $response->assertJson([
            'TransferCode' => '3998211',
            'TransactionId' => '3998211',
            'Status' => 'running',
            'WinLoss' => '150.00',
            'Stake' => '100.00',
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);
    }

    public static function productionParams()
    {
        return [
            ['IDR', '7DC996ABC2E642339147E5F776A3AE85'],
            ['THB', '7DC996ABC2E642339147E5F776A3AE85'],
            ['VND', '7DC996ABC2E642339147E5F776A3AE85'],
            ['BRL', '7DC996ABC2E642339147E5F776A3AE85'],
            ['USD', '7DC996ABC2E642339147E5F776A3AE85'],
        ];
    }

    public function test_status_invalidCompanyKey_expectedData()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID'
        ]);

        $request = [
            'CompanyKey' => 'invalid_company_key',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ];

        $response = $this->post('/sbo/prov/GetBetStatus', $request);

        $response->assertJson([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_status_playerNotFound_expectedData()
    {
        SboPlayer::factory()->create([
            'play_id' => 'player_id'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'invalid_id',
            'TransferCode' => '3998211'
        ];

        $response = $this->post('/sbo/prov/GetBetStatus', $request);

        $response->assertJson([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_status_transactionNotFound_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory
            {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet
                    {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 1000.0
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Bonus($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Cancel($payload)
                        {
                            return 0.0;
                        }

                        public function Resettle($payload)
                        {
                            return 0.0;
                        }

                        public function WagerAndPayout($payload, $report)
                        {
                            return 0.0;
                        }

                        public function TransferIn($payload)
                        {
                            return 0.0;
                        }

                        public function TransferOut($payload)
                        {
                            return 0.0;
                        }
                    };
                }
            };
        });

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'status' => 1
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'nonexistedTransactionID',
        ];

        $response = $this->post('/sbo/prov/GetBetStatus', $request);

        $response->assertJson([
            'ErrorCode' => 6,
            'ErrorMessage' => 'Bet not exists',
            'Balance' => 1000.0,
            'AccountName' => 'testPlayID'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider statusParams
     */
    public function test_status_incompleteRequest_expectedData($param)
    {
        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'player_id',
            'TransferCode' => '3998211'
        ];

        unset($request[$param]);

        $response = $this->post('/sbo/prov/GetBetStatus', $request);

        $response->assertJson([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);

        $response->assertStatus(200);
    }

    public static function statusParams()
    {
        return [
            ['CompanyKey'],
            ['Username'],
            ['TransferCode']
        ];
    }
}
