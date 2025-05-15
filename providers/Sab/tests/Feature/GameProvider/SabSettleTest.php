<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SabSettleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_settle_validDataAllFlagRunning_successWithoutBalanceResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    public function test_settle_validDataAllFlagUnsettled_successWithoutBalanceResponse()
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 4000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Netherlands',
                'game_code' => '1',
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

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Netherlands',
                'game_code' => '1',
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    public function test_settle_validDataOneRunningAndUnsettled_successWithoutBalanceResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }

            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Netherlands',
                'game_code' => '1',
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

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    #[DataProvider('currencyConversionParams')]
    public function test_settle_validDataCurrencyConversion_successWithoutBalanceResponse($currency, $value)
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => $currency,
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => $currency,
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
                'web_id' => 0,
                'currency' => $currency,
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
                'web_id' => 0,
                'currency' => $currency,
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 1
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 1
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => $value,
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => $value,
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

    public static function currencyConversionParams()
    {
        return [
            ['IDR', 1000],
            ['THB', 1],
            ['VND', 1000],
            ['BRL', 1],
            ['USD', 1]
        ];
    }

    public function test_settle_validDataParlayBet_successWithoutBalanceResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 12345,
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
                                    "bet_team" => "a",
                                    "ticket_status" => "win",
                                    "bettypename" => [
                                        [
                                            "name" => "Over/Under"
                                        ]
                                    ],
                                ]
                            ],
                            "settlement_time" => "2020-06-19T05:31:08.683",
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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
            'result' => '-',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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
            'result' => '-',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validDataVirtualSports_successWithoutBalanceResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetVirtualSportDetails" => [
                        [
                            "trans_id" => 12345,
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validDataNumberGame_successWithoutBalanceResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetNumberDetails" => [
                        [
                            "trans_id" => 12345,
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validDataOutrightBet_successWithoutBalanceResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 12345,
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_validDataZeroPayout_successWithoutBalanceResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 12345,
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.0,
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.0,
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 0.00
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 0.00
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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
            'result' => 'lose',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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
            'result' => 'lose',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_allUserIdNotExists_playerNotFoundResponse()
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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

    public function test_settle_oneUserIdNotExists_playerNotFoundResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    public function test_settle_allTxIdNotExists_transactionNotFoundResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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

    public function test_settle_oneTxIdNotExists_transactionNotFoundResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    public function test_settle_invalidKey_invalidKeyResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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

    public function test_settle_allFlagsInvalid_invalidTransactionStatusResponse()
    {
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 2000.0,
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

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 309,
            'msg' => 'Invalid Transaction Status'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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
            'result' => 'lose',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_settle_oneFlagValid_invalidTransactionStatusResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 2000.0,
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

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 309,
            'msg' => 'Invalid Transaction Status'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    public function test_settle_alltransactionAlreadyExists_transactionAlreadyExistFunction()
    {
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testOperationID-12345', // same operationId and txId with index 0 in request
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testOperationID-67890', // same operationId and txId with index 1 in request
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
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
    }

    public function test_settle_oneOperationIdTxIdNotExists_transactionAlreadyExistFunction()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testOperationID-12345', // same operationId & txId in request index 0
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
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
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    public function test_settle_getBetDetailErrorCodeNot0_thirdPartyApiErrorResponse()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 1,
            ]))
        ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 999,
            'msg' => 'System Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    #[DataProvider('getBetDetailResponse')]
    public function test_settle_missingThirdPartyApiResponseParameter_thirdPartyApiErrorResponse($key)
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 3000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $apiResponse = [
            'error_code' => 0,
            'Data' => []
        ];

        unset($apiResponse[$key]);

        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode($apiResponse))
        ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/settle', $request);

        $response->assertJson([
            'status' => 999,
            'msg' => 'System Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    public static function getBetDetailResponse()
    {
        return [
            ['error_code'],
            ['Data'],
        ];
    }

    public function test_settle_walletErrorPayout_walletErrorResponse()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testBetOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
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
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    public function test_settle_walletErrorResettle_walletErrorResponse()
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

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

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID1',
                'username' => 'testUsername1',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID2',
                'username' => 'testUsername2',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-12345',
                'trx_id' => '12345',
                'play_id' => 'testPlayID1',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000.0,
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
                'flag' => 'unsettled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'testPayoutOperationID-67890',
                'trx_id' => '67890',
                'play_id' => 'testPlayID2',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000.0,
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
                'flag' => 'unsettled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername1',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ],
                    [
                        'userId' => 'testUsername2',
                        'txId' => 67890,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            'bet_id' => 'testOperationID-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID1',
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

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'testOperationID-67890',
            'trx_id' => '67890',
            'play_id' => 'testPlayID2',
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

    #[DataProvider('settleParams')]
    public function test_settle_incompleteRequestParameter_invalidRequestResponse($key)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
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

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        elseif ($key === 'operationId')
            unset($request['message'][$key]);
        elseif ($key === 'txns')
            unset($request['message']['txns']);
        else
            unset($request['message']['txns'][0][$key]);

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
            ['message'],
            ['operationId'],
            ['txns'],
            ['userId'],
            ['txId'],
            ['updateTime'],
            ['payout']
        ];
    }
}
