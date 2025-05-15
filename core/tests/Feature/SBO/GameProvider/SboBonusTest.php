<?php

namespace Tests\Feature\Feature\SBO\GameProvider;

use Tests\TestCase;
use App\Models\SboPlayer;
use App\Models\SboReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class SboBonusTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_bonus_validRequest_expectedData()
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
                            return 0.0;
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
                            return [
                                'credit_after' => 900.0
                            ];
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
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'testTransactionID',
            'GameId' => 1
        ];

        $response = $this->post('sbo/prov/Bonus', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 900.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'bonus-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => 900.00,
            'bet_time' => '2020-01-02 12:00:00',
            'game_code' => '-',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => '1',

        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider productionParams
     */
    public function test_bonus_prodValidRequest_expectedData($currency, $companyKey)
    {
        config(['app.env' => 'PRODUCTION']);

        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory
            {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet
                    {
                        public function Balance($payload)
                        {
                            return 0.0;
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
                            return [
                                'credit_after' => 900.0
                            ];
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
            'play_id' => 'playID',
            'currency' => $currency
        ]);

        $request = [
            'CompanyKey' => $companyKey,
            'Username' => 'playID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'trxID',
            'GameId' => 1
        ];

        $response = $this->post('sbo/prov/Bonus', $request);

        $response->assertJson([
            'AccountName' => 'playID',
            'Balance' => 900.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'bonus-trxID',
            'trx_id' => 'trxID',
            'play_id' => 'playID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 0,
            'payout_amount' => 900.00,
            'bet_time' => '2020-01-02 12:00:00',
            'game_code' => '-',
            'odds' => 0,
            'flag' => 'bonus',
            'status' => '1',
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

    public function test_bonus_invalidCompanyKey_expectedData()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'CompanyKey' => 'invalid company key',
            'Username' => 'testPlayID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'testTransactionID',
            'GameId' => 1
        ];

        $response = $this->post('sbo/prov/Bonus', $request);

        $response->assertJson([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_bonus_playerNotFound_expectedData()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'invalidPlayID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'trxID',
            'GameId' => 1
        ];

        $response = $this->post('sbo/prov/Bonus', $request);

        $response->assertJson([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_bonus_transactionAlreadyExists_expectedData()
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
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'testTransactionID',
            'GameId' => 1
        ];

        $response = $this->post('sbo/prov/Bonus', $request);

        $response->assertJson([
            'ErrorCode' => 5003,
            'ErrorMessage' => 'Bet With Same RefNo Exists',
            'Balance' => 1000.0,
            'AccountName' => 'testPlayID'
        ]);

        $response->assertStatus(200);
    }

    public function test_bonus_invalidGrpcWalletResponse_expectedData()
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
                            return 0.0;
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
            'play_id' => 'playID',
            'currency' => 'IDR'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'playID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'trxID',
            'GameId' => 1
        ];

        $response = $this->post('sbo/prov/Bonus', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'bonus-trxID',
            'trx_id' => 'trxID',
            'play_id' => 'playID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => '0.000000',
            'payout_amount' => '900.000000',
            'bet_time' => '2020-01-02 12:00:00',
            'game_code' => '-',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => '0.000000',
            'result' => '-',
            'flag' => 'bonus',
            'status' => '1'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider bonusParams
     */
    public function test_bonus_incompleteRequest_expectedData($params)
    {
        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'testTransactionID',
            'GameId' => 1
        ];

        unset($request[$params]);

        $response = $this->post('sbo/prov/Bonus', $request);

        $response->assertJson([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);

        $response->assertStatus(200);
    }

    public static function bonusParams()
    {
        return [
            ['CompanyKey'],
            ['Username'],
            ['Amount'],
            ['BonusTime'],
            ['TransferCode'],
            ['GameId'],
        ];
    }
}
