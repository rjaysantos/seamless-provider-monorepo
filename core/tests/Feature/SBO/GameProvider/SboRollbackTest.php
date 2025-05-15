<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\SboPlayer;
use App\Models\SboReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class SboRollbackTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_rollback_validRequest_expected()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 250.0
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
                            return [
                                'credit_after' => 100.00
                            ];
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
            'bet_time' => '2021-01-01 00:00:00',
            'flag' => 'settled',
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 100.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'rollback-1-testTransactionID',
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 00:00:00',
            'result' => '-',
            'flag' => 'rollback',
            'status' => 1,
        ]);
    }

    public function test_rollback_validRequestAlreadyRollbackBefore_expected()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 250.0
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
                            return [
                                'credit_after' => 100.00
                            ];
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
            'bet_id' => 'payout-2-testTransactionID',
            'trx_id' => 'testTransactionID',
            'bet_time' => '2020-12-30 00:00:00',
            'flag' => 'settled'
        ]);

        SboReport::factory()->create([
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'bet_time' => '2020-12-30 00:00:00',
            'flag' => 'rollback'
        ]);

        SboReport::factory()->create([
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'bet_time' => '2021-01-01 00:00:00',
            'flag' => 'settled'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 100.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'rollback-2-testTransactionID',
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 00:00:00',
            'result' => '-',
            'flag' => 'rollback',
            'status' => 1,
        ]);
    }

    /**
     * @dataProvider productionParams
     */
    public function test_rollback_prodValidRequest_expected($currency, $companyKey)
    {
        config(['app.env' => 'PRODUCTION']);

        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 250.0
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
                            return [
                                'credit_after' => 100.00
                            ];
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
            'currency' => $currency
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'bet_time' => '2021-01-01 00:00:00',
            'flag' => 'void'
        ]);

        $request = [
            'CompanyKey' => $companyKey,
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 100.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'trx_id' => 'testTransactionID',
            'bet_time' => '2021-01-01 00:00:00',
            'flag' => 'rollback'
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

    public function test_rollback_invalidCompanyKey_expected()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 250.0
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
            'flag' => 'settled',
        ]);

        $request = [
            'CompanyKey' => 'InvalidCompanyKey',
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

    public function test_rollback_playerNotFound_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
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

    public function test_rollback_transactionNotFound_expected()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 250.0
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
            'flag' => 'settled'
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
            'Balance' => 250.0,
            'AccountName' => 'testPlayID'
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_transactionAlreadyRollback_expected()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 250.0
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
            'flag' => 'rollback'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Rollback', $request);

        $response->assertJson([
            'ErrorCode' => 2003,
            'ErrorMessage' => 'Bet Already Rollback',
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_emptyWalletResponse_expected()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
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
            'play_id' => 'testPlayID'
        ]);

        SboReport::factory()->create([
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => '100.000000',
            'payout_amount' => '200.000000',
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Virtual Sports',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => '3.400000',
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1'
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

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => '100.000000',
            'payout_amount' => '200.000000',
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Virtual Sports',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => '3.400000',
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1'
        ]);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => '100.000000',
            'payout_amount' => '0.000000',
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Virtual Sports',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => '3.400000',
            'result' => '-',
            'flag' => 'rollback',
            'status' => '1'
        ]);
    }

    /**
     * @dataProvider rollbackParams
     */
    public function test_rollback_incompleteParameter_expected($param)
    {
        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'invalidTransactionID',
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
            ['TransferCode'],
        ];
    }
}
