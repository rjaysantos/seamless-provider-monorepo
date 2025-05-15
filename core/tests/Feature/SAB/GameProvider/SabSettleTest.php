<?php

use Tests\TestCase;
use App\Models\SabPlayer;
use App\Models\SabReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SabSettleTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_settle_validRequestSingleBetWin_expectedData()
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 3000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "ParlayData" => null,
                            "bet_type" => 1,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validRequestParlayBetWin_expectedData()
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 3000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "hdp" => 3.4,
                            "sport_type" => 1,
                            "bet_type" => 29,
                            "odds_type" => 4,
                            "ticket_status" => "lose",
                            "bettypename" => [
                                [
                                    "name" => "System Parlay"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "ParlayData" => [
                                [
                                    "test"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => '29',
            'sports_type' => 'Mix Parlay',
            'event' => '-',
            'match' => 'Mix Parlay',
            'hdp' => '-',
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_betAlreadySettledBefore_expectedData()
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
                                'credit' => 1000.0,
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
                                'credit_after' => 3000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "ParlayData" => null,
                            "odds_type" => 4,
                            "bet_team" => "h",
                            "ticket_status" => "lose",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'settled',
            'status' => 0,
            'ip_address' => '123.456.7.8'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-2-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validRequestSingleBetMultipleTransactions_expectedData()
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 4000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "ParlayData" => null,
                            "odds_type" => 4,
                            "bet_team" => "h",
                            "ticket_status" => "lose",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID1',
            'username' => 'testPlayID1',
            'currency' => 'IDR'
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID2',
            'username' => 'testPlayID2',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID1',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285707',
            'play_id' => 'testPlayID2',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 2000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID1',
                        'refId' => 'testTransactionID1',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'status' => 'won'
                    ],
                    [
                        'userId' => 'testPlayID2',
                        'refId' => 'testTransactionID2',
                        'txId' => 277980818960285707,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 1,
                        'status' => 'lose'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID1',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285707',
            'trx_id' => '277980818960285707',
            'play_id' => 'testPlayID2',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 2000,
            'payout_amount' => 1000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'lose',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validMultipleTransactions1stFailed_expectedData()
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 4000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "ParlayData" => null,
                            "odds_type" => 4,
                            "bet_team" => "h",
                            "ticket_status" => "lose",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID1',
            'username' => 'testPlayID1',
            'currency' => 'IDR'
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID2',
            'username' => 'testPlayID2',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 'different_trx_id',
            'play_id' => 'testPlayID1',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285707',
            'play_id' => 'testPlayID2',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 2000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID1',
                        'refId' => 'testTransactionID1',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'status' => 'won'
                    ],
                    [
                        'userId' => 'testPlayID2',
                        'refId' => 'testTransactionID2',
                        'txId' => 277980818960285707,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 1,
                        'status' => 'lose'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 504,
            'msg' => 'No Such Ticket'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID1',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285707',
            'trx_id' => '277980818960285707',
            'play_id' => 'testPlayID2',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 2000,
            'payout_amount' => 1000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'lose',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validMultipleTransactionsAlreadySettled_expectedData()
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 4000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "ParlayData" => null,
                            "odds_type" => 4,
                            "bet_team" => "h",
                            "ticket_status" => "lose",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID1',
            'username' => 'testPlayID1',
            'currency' => 'IDR'
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID2',
            'username' => 'testPlayID2',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID1',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000.00,
            'payout_amount' => 0.0,
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285707',
            'play_id' => 'testPlayID2',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 2000.00,
            'payout_amount' => 0.0,
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID1',
                        'refId' => 'testTransactionID1',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'status' => 'won'
                    ],
                    [
                        'userId' => 'testPlayID2',
                        'refId' => 'testTransactionID2',
                        'txId' => 277980818960285707,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 1,
                        'status' => 'lose'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_validRequestParlayBetMultipleTransactions_expectedData()
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 4000.0
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

        // first getBetDetail for 277980818960285706
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 277980818960285706,
                            "odds" => 1.24,
                            "odds_type" => 4,
                            "sport_type" => 1,
                            "bet_type" => 29,
                            "bettypename" => [
                                [
                                    "name" => "System Parlay"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "ParlayData" => [
                                [
                                    "test",
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID1',
            'username' => 'testPlayID1',
            'currency' => 'IDR'
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID2',
            'username' => 'testPlayID2',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID1',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285707',
            'play_id' => 'testPlayID2',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 2000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID1',
                        'refId' => 'testTransactionID1',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'status' => 'won'
                    ],
                    [
                        'userId' => 'testPlayID2',
                        'refId' => 'testTransactionID2',
                        'txId' => 277980818960285707,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 1,
                        'status' => 'lose'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID1',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => '29',
            'sports_type' => 'Mix Parlay',
            'event' => '-',
            'match' => 'Mix Parlay',
            'hdp' => '-',
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285707',
            'trx_id' => '277980818960285707',
            'play_id' => 'testPlayID2',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 2000,
            'payout_amount' => 1000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => '29',
            'sports_type' => 'Mix Parlay',
            'event' => '-',
            'match' => 'Mix Parlay',
            'hdp' => '-',
            'odds' => 1.24,
            'result' => 'lose',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validRequestZeroPayout_expectedData()
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
                                'credit' => 100.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 100.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "ParlayData" => null,
                            "odds_type" => 4,
                            "bet_team" => "h",
                            "ticket_status" => "lose",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 277980818960285706,
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 0.00,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validRequestVirtualSports_expectedData()
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 4000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetVirtualSportDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
                            "sport_type" => 1,
                            "bet_type" => 2705,
                            "ParlayData" => null,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "Virtual Soccer Asian Cup"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Virtual Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683"
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '2705',
            'sports_type' => 'Virtual Soccer',
            'event' => 'Virtual Soccer Asian Cup',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validRequestNumberGame_expectedData()
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 4000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetNumberDetails" => [
                        [
                            "trans_id" => 277980818960285706,
                            "odds" => 1.24,
                            "odds_type" => 4,
                            "sport_type" => 161,
                            "bet_type" => 90,
                            "ticket_status" => "win",
                            "stake" => 10.00,
                            "settlement_time" => "2024-10-14T22:39:21.303",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Number Game',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validRequestOutright_expectedData()
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 4000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 277980818960285706,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => null,
                            "home_score" => null,
                            "away_score" => null,
                            "ParlayData" => null,
                            "sport_type" => 1,
                            "bet_type" => 10,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "leaguename" => [
                                [
                                    "name" => "2024/2025 UEFA CHAMPIONS LEAGUE - TOP GOALSCORER"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Outright"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => '10',
            'sports_type' => 'Soccer',
            'event' => '2024/2025 UEFA CHAMPIONS LEAGUE - TOP GOALSCORER',
            'match' => '-',
            'hdp' => '-',
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    /**
     * @dataProvider oneToOneCurrency
     */
    public function test_settle_validRequestOneToOneCurrency_expectedData($currency)
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 3000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "ParlayData" => null,
                            "bet_type" => 1,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2000,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public static function oneToOneCurrency()
    {
        return [
            ['THB'],
            ['BRL'],
            ['USD'],
        ];
    }

    /**
     * @dataProvider oneToOneThousandCurrency
     */
    public function test_settle_validRequestOneToOneThousandCurrency_expectedData($currency)
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 3000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "ParlayData" => null,
                            "bet_type" => 1,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public static function oneToOneThousandCurrency()
    {
        return [
            ['IDR'],
            ['VND'],
        ];
    }

    /**
     * @dataProvider productionOneToOneCurrency
     */
    public function test_settle_prodValidRequestOneToOneCurrency_expectedData($currency)
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 4000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "ParlayData" => null,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => 'oe880dd8en',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3000,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public static function productionOneToOneCurrency()
    {
        return [
            ['THB'],
            ['BRL'],
            ['USD'],
        ];
    }

    /**
     * @dataProvider productionOneToOneThousandCurrency
     */
    public function test_settle_prodValidRequestOneToOneThousandCurrency_expectedData($currency)
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
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 4000.0
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "ParlayData" => null,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => 'oe880dd8en',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public static function productionOneToOneThousandCurrency()
    {
        return [
            ['IDR'],
            ['VND'],
        ];
    }

    public function test_settle_validRequestSingleBetWinWalletError_expectedData()
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
                                'credit' => 1000.0,
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "ParlayData" => null,
                            "bet_type" => 1,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_invalidKey_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 277980818960285706,
            'flag' => 'running',
            'status' => 1
        ]);

        $request = [
            'key' => 'invalidKey',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 300.00,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_playerNotFound_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'invalidPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 300.00,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_transactionNotFound_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 277980818960285707,
            'flag' => 'running',
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 300.00,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 504,
            'msg' => 'No Such Ticket'
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_emptyWalletResponse_expectedData()
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
                            return null;
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
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "ParlayData" => null,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "hometeamname" => [
                                [
                                    "name" => "Netherlands"
                                ]
                            ],
                            "awayteamname" => [
                                [
                                    "name" => "Portugal"
                                ]
                            ],
                            "leaguename" => [
                                [
                                    "name" => "SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS"
                                ]
                            ],
                            "sportname" => [
                                [
                                    "name" => "Soccer"
                                ]
                            ],
                            "bettypename" => [
                                [
                                    "name" => "Handicap"
                                ]
                            ],
                        ]
                    ]
                ]
            ]))
        ]);

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 277980818960285706,
            'play_id' => 'testPlayID',
            'flag' => 'running',
            'status' => 1,
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 300.00,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider settleParams
     */
    public function test_settle_incompleteRequest_expectedData($param)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 300.00,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        unset($request[$param]);

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function settleParams()
    {
        return [
            ['key'],
            ['message']
        ];
    }
}
