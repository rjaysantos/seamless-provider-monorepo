<?php

use Tests\TestCase;
use App\Models\SboPlayer;
use App\Models\SboReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SboSettleTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_settle_validRequest_expected()
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
                            return [
                                'credit_after' => 1200.0
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => 'Over',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'payout_amount' => 0,
            'game_code' => 0,
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 1200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'payout_amount' => 200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1',
        ]);
    }

    public function test_settle_validRequestOdsTypeNull_expected()
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
                            return [
                                'credit_after' => 1200.0
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => 'Over',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => null
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'payout_amount' => 0,
            'game_code' => 0,
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 1200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'payout_amount' => 200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1',
        ]);
    }

    public function test_settle_validRequestParlay_expected()
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
                            return [
                                'credit_after' => 1200.0
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'e-Wales vs e-Austria',
                                'marketType' => 'Handicap',
                                'hdp' => '2.50',
                                'odds' => 1.98,
                                'betOption' => 'e-Austria',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'e-Football F23 International Friendly'
                            ],
                            [
                                'match' => 'e-Everton vs e-Bayern Munchen',
                                'marketType' => 'Handicap',
                                'hdp' => '0.50',
                                'odds' => 1.73,
                                'betOption' => 'e-Everton',
                                'status' => 'won',
                                'ftScore' => '1:1',
                                'liveScore' => '0:1',
                                'htScore' => '0:1',
                                'league' => 'e-Football F23 Elite Club Friendly'
                            ],
                            [
                                'match' => 'e-Poland vs e-Germany',
                                'marketType' => 'Handicap',
                                'hdp' => '2.50',
                                'odds' => 1.67,
                                'betOption' => 'e-Poland',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'e-Football F23 International Friendly'
                            ]
                        ],
                        'sportsType' => 'Mix Parlay',
                        'oddsStyle' => 'E',
                        'odds' => 5.70
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'payout_amount' => 0,
            'game_code' => 0,
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 1200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'payout_amount' => 200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => '-',
            'sports_type' => 'Mix Parlay',
            'event' => '-',
            'match' => 'Mix Parlay',
            'hdp' => '0',
            'odds' => 5.70,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1',
        ]);
    }

    public function test_settle_validRequestMiniGame_expected()
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
                            return [
                                'credit_after' => 1200.0
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
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'fkg_200006173045',
            'payout_amount' => 0,
            'game_code' => 286,
            'result' => '-',
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'fkg_200006173045',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 9,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 1200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-fkg_200006173045',
            'trx_id' => 'fkg_200006173045',
            'payout_amount' => 200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'ARCADE',
            'game_code' => 'Mini Football Strike',
            'sports_type' => 'ARCADE',
            'event' => 'ARCADE',
            'match' => 'ARCADE',
            'hdp' => '-',
            'odds' => 0,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1',
        ]);
    }

    public function test_settle_validRequestRngGame_expected()
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
                                'credit_after' => 450.0
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
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'bet_amount' => 50.00,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 00:00:00',
            'result' => '-',
            'flag' => 'running',
            'status' => '0',
        ]);

        SboReport::factory()->create([
            'bet_id' => 'wager-2-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'bet_amount' => 100.00,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 00:00:00',
            'result' => '-',
            'flag' => 'running-inc',
            'status' => '1',
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 3,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 450.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'resettle-2-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'bet_amount' => 100.00,
            'payout_amount' => 200.0,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
        ]);
    }

    /**
     * @dataProvider productionParams
     */
    public function test_settle_prodValidRequest_expected($currency, $companyKey)
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
                            return [
                                'credit_after' => 1200.0
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => 'Over',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => $currency,
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'payout_amount' => 0,
            'game_code' => 0,
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => $companyKey,
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 1200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'payout_amount' => 200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1',
        ]);
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

    /**
     * @dataProvider resultParams
     */
    public function test_settle_validRequestMultipleResult_expected($bet, $payout, $result, $IsCashOut = false)
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
                            return [
                                'credit_after' => 1200.0
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => 'Over',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'bet_amount' => $bet,
            'game_code' => 0,
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => $payout,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => $IsCashOut
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 1200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'payout_amount' => $payout,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => $result,
            'flag' => 'settled',
            'status' => '1',
        ]);
    }

    public static function resultParams()
    {
        return [
            [100.00, 500.00, 'win'],
            [100.00, 100.00, 'draw'],
            [100.00, 90.00, 'cash out', true],
            [100.00, 0.00, 'lose']
        ];
    }

    /**
     * @dataProvider betOptionParams
     */
    public function test_settle_validRequestMultipleBetOption_expected($betOption, $match)
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
                            return [
                                'credit_after' => 1200.0
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => $betOption,
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'payout_amount' => 0,
            'game_code' => 0,
            'flag' => 'running'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 1200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'payout_amount' => 200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => $match,
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1',
        ]);
    }

    public static function betOptionParams()
    {
        return [
            [1,       'Denmark'],
            ['Over',  'Over'],
            [2,       'England'],
            ['Under', 'Under'],
            ['draw',  'draw'],
            ['X',     'draw'],
        ];
    }

    /**
     * @dataProvider flagParams
     */
    public function test_settle_validRequestMultipleFlag_expected($param)
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
                            return  [
                                'credit_after' => 1200.0
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => 'Over',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'payout_amount' => 100.0,
            'game_code' => 0,
            'flag' => 'settled',
            'status' => 0
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'payout_amount' => 0,
            'game_code' => 0,
            'bet_time' => '2021-01-01 00:00:00',
            'flag' => $param,
            'status' => 1
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 1200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'resettle-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'payout_amount' => 200.0,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'Over',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => '1',
        ]);
    }

    public static function flagParams()
    {
        return [
            ['rollback'],
            ['running-inc'],
        ];
    }

    public function test_settle_invalidCompanyKey_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'payout_amount' => 100.0,
            'game_code' => 0,
            'flag' => 'settled',
            'status' => 0
        ]);

        $request = [
            'CompanyKey' => 'invalid_company_key',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_playerNotFound_expected()
    {
        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'invalidPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_transactionNotFound_expected()
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
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'InvalidTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 6,
            'ErrorMessage' => 'Bet not exists',
            'Balance' => 250,
            'AccountName' => 'testPlayID',
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_transactionAlreadySettled_expected()
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => 'Over',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'flag' => 'settled'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 2001,
            'ErrorMessage' => 'Bet Already Settled',
            'Balance' => 250,
            'AccountName' => 'testPlayID',
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_transactionAlreadyVoid_expected()
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => 'Over',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
        ]);

        SboReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'flag' => 'void'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 2002,
            'ErrorMessage' => 'Bet Already Cancelled',
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_invalidGrpcWalletResponse_expected()
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
                            return null;
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

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'hdp' => '2.5',
                                'odds' => 3.40,
                                'betOption' => 'Over',
                                'status' => 'won',
                                'ftScore' => '2:0',
                                'liveScore' => '0:0',
                                'htScore' => '0:0',
                                'league' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)'
                            ]
                        ],
                        'oddsStyle' => 'E'
                    ]
                ],
                'error' => [
                    'id' => 0
                ]
            ]))
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'ip_address' => '123.456.7.8'
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
            'game_code' => '123',
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
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
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
    }

    /**
     * @dataProvider settleParams
     */
    public function test_settle_incompleteRequest_expected($param)
    {
        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        unset($request[$param]);

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);

        $response->assertStatus(200);
    }

    public static function settleParams()
    {
        return [
            ['CompanyKey'],
            ['Username'],
            ['TransferCode'],
            ['WinLoss'],
            ['ResultTime'],
            ['ProductType'],
            ['IsCashOut']
        ];
    }
}
