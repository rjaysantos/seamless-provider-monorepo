<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;

class SabSequenceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_placeBetConfirmBetSettle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOpeationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOpeationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // Settle

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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID-12345',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetParlayConfirmBetParlaySettle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBetParlay

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1',
                        'betAmount' => 1
                    ],
                    [
                        'refId' => 'testTransactionID2',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID1',
                    'licenseeTxId' => 'testTransactionID1'
                ],
                [
                    'refId' => 'testTransactionID2',
                    'licenseeTxId' => 'testTransactionID2'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID1',
            'trx_id' => 'testTransactionID1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ],
                    [
                        'refId' => 'testTransactionID2',
                        'txId' => 67890,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
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

        // Settle

        Http::fake([
            'api/GetBetDetailByTransID' => Http::sequence()
                ->push(json_encode([
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
                ->push(json_encode([
                    "error_code" => 0,
                    "Data" => [
                        "BetDetails" => [
                            [
                                "trans_id" => 67890,
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
                                        "name" => "SABA ELITE FRIENDLY Virtual PES 24 - PENALTY SHOOTOUTS"
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
                                "settlement_time" => "2020-06-20T05:31:08.683",
                            ]
                        ]
                    ]
                ]))
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername',
                        'txId' => 67890,
                        'updateTime' => '2021-01-02T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 1,
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testSettleOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-02 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 1,
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 24 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetCancelBet_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // CancelBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testCancelOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null,
            'balance' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testCancelOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetParlayCancelBet_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBetParlay

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1',
                        'betAmount' => 1
                    ],
                    [
                        'refId' => 'testTransactionID2',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID1',
                    'licenseeTxId' => 'testTransactionID1'
                ],
                [
                    'refId' => 'testTransactionID2',
                    'licenseeTxId' => 'testTransactionID2'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID1',
            'trx_id' => 'testTransactionID1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
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

        // CancelBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testCancelOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID1'
                    ],
                    [
                        'refId' => 'testTransactionID2'
                    ],
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null,
            'balance' => 2
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testCancelOperationID-testTransactionID1',
            'trx_id' => 'testTransactionID1',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testCancelOperationID-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetConfirmBetSettleResettle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }

            public function Resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // Settle

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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID-12345',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Resettle

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testResettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-02T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testResettleOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-02 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetConfirmBetSettleResettleResettle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }

            public function Resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // Settle

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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID-12345',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Resettle 1

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testResettleOperationID1',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-02T00:00:00.000-04:00',
                        'payout' => 0,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testResettleOperationID1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-02 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Resettle 2

        $wallet = new class extends TestWallet {
            public function Resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testResettleOperationID2',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-03T00:00:00.000-04:00',
                        'payout' => 1,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testResettleOperationID2-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 1000,
            'bet_time' => '2021-01-03 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetConfirmBetSettleUnsettleSettle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }

            public function Resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // Settle 1

        Http::fake([
            'api/GetBetDetailByTransID' => Http::sequence()
                ->push(json_encode([
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
                ->push(json_encode([
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
                                "ParlayData" => null,
                                "bet_type" => 1,
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID1',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID1-12345',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Unsettle

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testUnsettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/unsettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testUnsettleOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000.00,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 1,
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'unsettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Settle 2

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID2',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 0
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
            'bet_id' => 'testSettleOperationID2-12345',
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
            'result' => 'lose',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetConfirmBetSettleUnsettleSettleResettle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }

            public function Resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // Settle 1

        Http::fake([
            'api/GetBetDetailByTransID' => Http::sequence()
                ->push(json_encode([
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
                ->push(json_encode([
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
                                "ParlayData" => null,
                                "bet_type" => 1,
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID1',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID1-12345',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Unsettle

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testUnsettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/unsettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testUnsettleOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000.00,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 1,
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'unsettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Settle 2

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID2',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 0
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
            'bet_id' => 'testSettleOperationID2-12345',
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
            'result' => 'lose',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Resettle

        $wallet = new class extends TestWallet {
            public function Resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testResettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-02T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testResettleOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-02 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'lose',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetConfirmBetAdjustBalanceSettle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }

            public function TransferIn(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOpeationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOpeationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // AdjustBalance

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testAdjustBalanceOperationID',
                'userId' => 'testUsername',
                'txId' => 13579,
                'refNo' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testAdjustBalanceOperationID-13579',
            'trx_id' => '13579',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
            'ip_address' => null
        ]);

        // Settle

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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-02T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-02 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_placeBetConfirmBetSettleAdjustBalance_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }

            public function TransferIn(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOpeationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOpeationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // Settle

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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-02T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 2000,
            'bet_time' => '2021-01-02 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // AdjustBalance

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testAdjustBalanceOperationID',
                'userId' => 'testUsername',
                'txId' => 13579,
                'refNo' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testAdjustBalanceOperationID-13579',
            'trx_id' => '13579',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
            'ip_address' => null
        ]);
    }

    public function test_placeBetConfirmBetSettleResettleAdjustBalance_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }

            public function Resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function TransferIn(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // Settle

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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID-12345',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Resettle

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testResettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-02T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testResettleOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-02 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // AdjustBalance

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testAdjustBalanceOperationID',
                'userId' => 'testUsername',
                'txId' => 13579,
                'refNo' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testAdjustBalanceOperationID-13579',
            'trx_id' => '13579',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
            'ip_address' => null
        ]);
    }

    public function test_placeBetConfirmBetSettleAdjustBalanceAdjustBalanceResettle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }

            public function Resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function TransferIn(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }

            public function TransferOut(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betTime): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        // PlaceBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testPlaceBetOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        $response = $this->post('/sab/prov/placebet', $request);

        $response->assertJson([
            'status' => 0,
            'refId' => 'testTransactionID',
            'licenseeTxId' => 'testTransactionID',
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testPlaceBetOperationID-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2020-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // ConfirmBet

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testConfirmBetOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testConfirmBetOperationId-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
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

        // Settle

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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testSettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testSettleOperationID-12345',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // Resettle

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testResettleOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-02T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testResettleOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-02 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => '1',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'win',
            'flag' => 'resettled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        // AdjustBalance 1

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testAdjustBalanceOperationID1',
                'userId' => 'testUsername',
                'txId' => 13579,
                'refNo' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testAdjustBalanceOperationID1-13579',
            'trx_id' => '13579',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
            'ip_address' => null
        ]);

        // AdjustBalance 2

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testAdjustBalanceOperationID2',
                'userId' => 'testUsername',
                'txId' => 24680,
                'refNo' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 1,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 0,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testAdjustBalanceOperationID2-24680',
            'trx_id' => '24680',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => -1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 17003,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
            'ip_address' => null
        ]);
    }
}