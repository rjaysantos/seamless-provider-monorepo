<?php

use Tests\TestCase;
use App\Models\SboPlayer;
use App\Models\SboReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class SboCancelTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_cancel_validRequestRunning_expectedData()
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
                            return [
                                'credit_after' => 150.0
                            ];
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
                                'credit_after' => 350.0
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
            'play_id' => 'testPlayID',
            'bet_amount' => 100.00,
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0,
            'flag' => 'running',
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 350.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'bet_amount' => 100.00,
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0,
            'result' => 'void',
            'flag' => 'void',
            'status' => 1,
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_validRequestRollback_expectedData()
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
                                'credit_after' => 350.0
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
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.0,
            'bet_time' => '2021-01-01 00:00:00',
            'flag' => 'void'
        ]);

        SboReport::factory()->create([
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.0,
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0.0,
            'flag' => 'rollback'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 350.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-2-testTransactionID',
            'trx_id' => 'testTransactionID',
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0,
            'flag' => 'void',
            'status' => 1
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_validDataTransactionAlreadySettled_expectedData()
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
                                'credit_after' => 150.0
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
            'currency' => 'IDR'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 200.000,
            'flag' => 'settled'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 150.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0,
            'flag' => 'void',
            'status' => 1
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_validDataRNGRunning_expectedData()
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
                            return [
                                'credit_after' => 250.0
                            ];
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
                                'credit_after' => 350.0
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
            'bet_id' => 'wager-1-testRNGTransactionID',
            'trx_id' => 'testRNGTransactionID',
            'bet_amount' => 100.0,
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0.0,
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testRNGTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 350.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-1-testRNGTransactionID',
            'trx_id' => 'testRNGTransactionID',
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0,
            'flag' => 'void',
            'status' => 1
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_validDataRNGAlreadySettled_expectedData()
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
                                'credit_after' => 350.0
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
            'bet_id' => 'wager-1-testRNGTransactionID',
            'trx_id' => 'testRNGTransactionID',
            'bet_amount' => 100.0,
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 0
        ]);

        SboReport::factory()->create([
            'bet_id' => 'wager-2-testRNGTransactionID',
            'trx_id' => 'testRNGTransactionID',
            'bet_amount' => 200.0,
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0.0,
            'flag' => 'running-inc',
            'status' => 1
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testRNGTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 350.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-1-testRNGTransactionID',
            'trx_id' => 'testRNGTransactionID',
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0,
            'flag' => 'void',
            'status' => 1
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider productionParams
     */
    public function test_cancel_prodValidRequest_expectedData($currency, $companyKey)
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
                            return [
                                'credit_after' => 250.0
                            ];
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
                                'credit_after' => 350.0
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
            'currency' => $currency,
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'bet_time' => '2021-01-01 00:00:00',
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => $companyKey,
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 350.00,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'bet_time' => '2021-01-01 00:00:00',
            'payout_amount' => 0,
            'flag' => 'void',
            'status' => 1
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

    public function test_cancel_invalidCompanyKey_expected()
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
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => 'InvalidCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_playerNotFound_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'invalidPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_transactionNotFound_expected()
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
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'invalidTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 6,
            'ErrorMessage' => 'Bet not exists',
            'Balance' => 250,
            'AccountName' => 'testPlayID'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_transactionAlreadyVoid_expected()
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
            'flag' => 'void'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $response = $this->post('/sbo/prov/Cancel', $request);

        $response->assertJson([
            'ErrorCode' => 2002,
            'ErrorMessage' => 'Bet Already Cancelled',
        ]);

        $response->assertStatus(200);
    }

    public function test_cancel_emptyWalletResponse_expected()
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

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
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

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'cancel-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => '100.000000',
            'payout_amount' => '0.000000',
            'bet_time' => '2021-06-01 12:23:25',
            'bet_choice' => '-',
            'game_code' => '0',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => '0.00',
            'result' => 'void',
            'flag' => 'void',
            'status' => '1'
        ]);
    }

    /**
     * @dataProvider cancelParams
     */
    public function test_cancel_incompleteParameter_expected($param)
    {
        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'invalidTransactionID',
        ];

        unset($request[$param]);

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
            ['TransferCode'],
        ];
    }
}
