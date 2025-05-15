<?php

use Tests\TestCase;
use App\Models\SabPlayer;
use App\Models\SabReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SabTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_placebetConfirmBetSettleResettle_validRequest_expectedData()
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
                                'credit' => 2000.0
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return [
                                'credit_after' => 1000.0
                            ];
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
                            return [
                                'credit_after' => 4000.0
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

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        // call PlaceBet

        $placeBetRequest = [
            'key' => '96l542m8kr',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $placeBetResponse = $this->post('/sab/prov/placebet', $placeBetRequest);

        $placeBetResponse->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $placeBetResponse->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => '1',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'waiting',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);

        // call ConfirmBet

        $confirmBetRequest = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'actualAmount' => 1,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $confirmBetResponse = $this->post('/sab/prov/confirmbet', $confirmBetRequest);

        $confirmBetResponse->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $confirmBetResponse->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'confirmBet-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
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
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);

        // call Settle

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 12345,
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

        $settleRequest = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $settleResponse = $this->post('/sab/prov/settle', $settleRequest);

        $settleResponse->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $settleResponse->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-12345',
            'trx_id' => '12345',
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

        // call Resettle

        $resettleRequest = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'won',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $resettleResponse = $this->post('/sab/prov/resettle', $resettleRequest);

        $resettleResponse->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $resettleResponse->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'resettle-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
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
    }

    public function test_placeBetConfirmBetSettleUnsettleSettle_validRequest_expectedData()
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
                                'credit' => 2000.0
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return [
                                'credit_after' => 1000.0
                            ];
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
                            return [
                                'credit_after' => 5000.0
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

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        // call PlaceBet

        $placeBetRequest = [
            'key' => '96l542m8kr',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $placeBetResponse = $this->post('/sab/prov/placebet', $placeBetRequest);

        $placeBetResponse->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $placeBetResponse->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => '1',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'waiting',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);

        // call ConfirmBet

        $confirmBetRequest = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'actualAmount' => 1,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $confirmBetResponse = $this->post('/sab/prov/confirmbet', $confirmBetRequest);

        $confirmBetResponse->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $confirmBetResponse->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'confirmBet-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
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
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);

        // call Settle

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 12345,
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

        $settleRequest = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $settleResponse = $this->post('/sab/prov/settle', $settleRequest);

        $settleResponse->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $settleResponse->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-1-12345',
            'trx_id' => '12345',
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

        // call Unsettle

        $unsettleRequest = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        $unsettleResponse = $this->post('/sab/prov/unsettle', $unsettleRequest);

        $unsettleResponse->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $unsettleResponse->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'unsettle-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'won',
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // call Settle again

        $settleRequest = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 4,
                        'status' => 'won'
                    ]
                ]
            ]
        ];

        $settleResponse = $this->post('/sab/prov/settle', $settleRequest);

        $settleResponse->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $settleResponse->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'payout-2-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 4000,
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
}
