<?php

use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SabOutstandingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
    }

    public function test_outstanding_validRequest_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 282450939317583007,
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
                            "settlement_time" => "2024-10-14T22:39:21.303",
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        DB::table('sab.reports')
            ->insert([
                [
                    'bet_id' => 'confirmBet-1-282450939317583007',
                    'trx_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'web_id' => 27,
                    'currency' => 'IDR',
                    'bet_amount' => 100.00,
                    'payout_amount' => 0,
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_choice' => '-',
                    'game_code' => '1',
                    'sports_type' => '-',
                    'event' => '-',
                    'match' => '-',
                    'hdp' => '',
                    'odds' => 0,
                    'result' => '-',
                    'flag' => 'running',
                    'status' => '1',
                    'created_at' => '2024-07-10 12:23:25',
                    'updated_at' => '2024-07-10 12:23:25',
                    'ip_address' => '123.456.7.8',
                ],
                [
                    'bet_id' => 'confirmBet-1-282450939317583008',
                    'trx_id' => '282450939317583008',
                    'play_id' => 'testPlayID2',
                    'web_id' => 27,
                    'currency' => 'IDR',
                    'bet_amount' => 100.00,
                    'payout_amount' => 0,
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_choice' => '-',
                    'game_code' => '1',
                    'sports_type' => '-',
                    'event' => '-',
                    'match' => '-',
                    'hdp' => '',
                    'odds' => 0,
                    'result' => '-',
                    'flag' => 'settled',
                    'status' => '1',
                    'created_at' => '2021-06-02 12:23:25',
                    'updated_at' => '2024-07-10 12:23:25',
                    'ip_address' => '123.456.7.8',
                ]
            ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start'    => 0,
            'length'   => 10,
        ];

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'data' => [
                [
                    'id' => '282450939317583007',
                    'bet_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'game_type' => 'Handicap',
                    'league' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                    'match' => 'Netherlands vs Portugal',
                    'bet_option' => 'Netherlands',
                    'amount' => '100.000000',
                    'hdp' => '3.4',
                    'odds' => '1.24',
                    'odds_type' => 'Decimal Odds',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => 'Handicap',
                    'bet_choice' => 'Netherlands',
                    'bet_type' => 'Handicap',
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);

        $this->assertTrue(
            Str::contains(
                $response->getContent(),
                'visual'
            )
        );
    }

    public function test_outstanding_validRequestParlayData_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 282450939317583007,
                            "odds" => 1.24,
                            "sport_type" => 1,
                            "bet_type" => 29,
                            "odds_type" => 4,
                            "ticket_status" => "won",
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
                            "settlement_time" => "2024-10-14T22:39:21.303",
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        DB::table('sab.reports')
            ->insert([
                [
                    'bet_id' => 'confirmBet-1-282450939317583007',
                    'trx_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'web_id' => 27,
                    'currency' => 'IDR',
                    'bet_amount' => 100.00,
                    'payout_amount' => 0,
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_choice' => '-',
                    'game_code' => '29',
                    'sports_type' => '-',
                    'event' => '-',
                    'match' => '-',
                    'hdp' => '',
                    'odds' => 0,
                    'result' => '-',
                    'flag' => 'running',
                    'status' => '1',
                    'created_at' => '2024-07-10 12:23:25',
                    'updated_at' => '2024-07-10 12:23:25',
                    'ip_address' => '123.456.7.8',
                ],
                [
                    'bet_id' => 'confirmBet-1-282450939317583008',
                    'trx_id' => '282450939317583008',
                    'play_id' => 'testPlayID2',
                    'web_id' => 27,
                    'currency' => 'IDR',
                    'bet_amount' => 100.00,
                    'payout_amount' => 0,
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_choice' => '-',
                    'game_code' => '29',
                    'sports_type' => '-',
                    'event' => '-',
                    'match' => '-',
                    'hdp' => '',
                    'odds' => 0,
                    'result' => '-',
                    'flag' => 'settled',
                    'status' => '1',
                    'created_at' => '2021-06-02 12:23:25',
                    'updated_at' => '2024-07-10 12:23:25',
                    'ip_address' => '123.456.7.8',
                ]
            ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start'    => 0,
            'length'   => 10,
        ];

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'data' => [
                [
                    'id' => '282450939317583007',
                    'bet_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'game_type' => 'System Parlay',
                    'league' => '-',
                    'match' => 'Mix Parlay',
                    'bet_option' => '-',
                    'amount' => '100.000000',
                    'hdp' => '-',
                    'odds' => '1.24',
                    'odds_type' => 'Indo Odds',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => 'System Parlay',
                    'bet_choice' => '-',
                    'bet_type' => 'System Parlay',
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);
    }

    public function test_outstanding_validRequestMultipleSingleBetData_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 282450939317583007,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
                            "home_score" => 1,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "away_score" => 0,
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
                            "settlement_time" => "2024-10-14T22:39:21.303",
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.players')->insert([
            [
                'play_id' => 'testPlayID',
                'username' => 'AIX_testPlayID_test',
                'currency' => 'IDR',
                'game' => 0
            ],
            [
                'play_id' => 'testPlayID2',
                'username' => 'AIX_testPlayID2_test',
                'currency' => 'IDR',
                'game' => 0
            ]
        ]);

        DB::table('sab.reports')
            ->insert([
                [
                    'bet_id' => 'confirmBet-1-282450939317583007',
                    'trx_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'web_id' => 27,
                    'currency' => 'IDR',
                    'bet_amount' => 100.00,
                    'payout_amount' => 0,
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_choice' => '-',
                    'game_code' => '1',
                    'sports_type' => '-',
                    'event' => '-',
                    'match' => '-',
                    'hdp' => '',
                    'odds' => 0,
                    'result' => '-',
                    'flag' => 'running',
                    'status' => '1',
                    'created_at' => '2024-07-10 12:23:25',
                    'updated_at' => '2024-07-10 12:23:25',
                    'ip_address' => '123.456.7.8',
                ],
                [
                    'bet_id' => 'confirmBet-1-282450939317583008',
                    'trx_id' => '282450939317583008',
                    'play_id' => 'testPlayID2',
                    'web_id' => 27,
                    'currency' => 'IDR',
                    'bet_amount' => 100.00,
                    'payout_amount' => 0,
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_choice' => '-',
                    'game_code' => '1',
                    'sports_type' => '-',
                    'event' => '-',
                    'match' => '-',
                    'hdp' => '',
                    'odds' => 0,
                    'result' => '-',
                    'flag' => 'running',
                    'status' => '1',
                    'created_at' => '2021-06-02 12:23:25',
                    'updated_at' => '2024-07-10 12:23:25',
                    'ip_address' => '123.456.7.8',
                ]
            ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start'    => 0,
            'length'   => 10,
        ];

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'data' => [
                [
                    'id' => '282450939317583007',
                    'bet_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'game_type' => 'Handicap',
                    'league' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                    'match' => 'Netherlands vs Portugal',
                    'bet_option' => 'Netherlands',
                    'amount' => '100.000000',
                    'hdp' => '3.4',
                    'odds' => '1.24',
                    'odds_type' => 'Decimal Odds',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => 'Handicap',
                    'bet_choice' => 'Netherlands',
                    'bet_type' => 'Handicap',
                ],
                [
                    'id' => '282450939317583008',
                    'bet_id' => '282450939317583008',
                    'play_id' => 'testPlayID2',
                    'game_type' => 'Handicap',
                    'league' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
                    'match' => 'Netherlands vs Portugal',
                    'bet_option' => 'Netherlands',
                    'amount' => '100.000000',
                    'hdp' => '3.4',
                    'odds' => '1.24',
                    'odds_type' => 'Decimal Odds', //not sure if int
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => 'Handicap',
                    'bet_choice' => 'Netherlands',
                    'bet_type' => 'Handicap',
                ],
            ],
            'recordsTotal' => 2,
            'recordsFiltered' => 2
        ]);
    }

    public function test_outstanding_validRequestMultipleParlayBetData_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 282450939317583007,
                            "odds" => 1.24,
                            "sport_type" => 1,
                            "bet_type" => 29,
                            "odds_type" => 4,
                            "ticket_status" => "won",
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
                            "settlement_time" => "2024-10-14T22:39:21.303",
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.players')->insert([
            [
                'play_id' => 'testPlayID',
                'username' => 'AIX_testPlayID_test',
                'currency' => 'IDR',
                'game' => 0
            ],
            [
                'play_id' => 'testPlayID2',
                'username' => 'AIX_testPlayID2_test',
                'currency' => 'IDR',
                'game' => 0
            ],
        ]);

        DB::table('sab.reports')->insert([
            [
                'bet_id' => 'confirmBet-1-282450939317583007',
                'trx_id' => '282450939317583007',
                'play_id' => 'testPlayID',
                'web_id' => 27,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0,
                'bet_time' => '2024-07-10 12:23:25',
                'bet_choice' => '-',
                'game_code' => '29',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '',
                'odds' => 0,
                'result' => '-',
                'flag' => 'running',
                'status' => '1',
                'created_at' => '2024-07-10 12:23:25',
                'updated_at' => '2024-07-10 12:23:25',
                'ip_address' => '123.456.7.8',
            ],
            [
                'bet_id' => 'confirmBet-1-282450939317583008',
                'trx_id' => '282450939317583008',
                'play_id' => 'testPlayID2',
                'web_id' => 27,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0,
                'bet_time' => '2024-07-10 12:23:25',
                'bet_choice' => '-',
                'game_code' => '29',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '',
                'odds' => 0,
                'result' => '-',
                'flag' => 'running',
                'status' => '1',
                'created_at' => '2021-06-02 12:23:25',
                'updated_at' => '2024-07-10 12:23:25',
                'ip_address' => '123.456.7.8',
            ]
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start'    => 0,
            'length'   => 10,
        ];

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'data' => [
                [
                    'id' => '282450939317583007',
                    'bet_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'game_type' => 'System Parlay',
                    'league' => '-',
                    'match' => 'Mix Parlay',
                    'bet_option' => '-',
                    'amount' => '100.000000',
                    'hdp' => '-',
                    'odds' => '1.24',
                    'odds_type' => 'Indo Odds',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => 'System Parlay',
                    'bet_choice' => '-',
                    'bet_type' => 'System Parlay',
                ],
                [
                    'id' => '282450939317583008',
                    'bet_id' => '282450939317583008',
                    'play_id' => 'testPlayID2',
                    'game_type' => 'System Parlay',
                    'league' => '-',
                    'match' => 'Mix Parlay',
                    'bet_option' => '-',
                    'amount' => '100.000000',
                    'hdp' => '-',
                    'odds' => '1.24',
                    'odds_type' => 'Indo Odds',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => 'System Parlay',
                    'bet_choice' => '-',
                    'bet_type' => 'System Parlay',
                ],
            ],
            'recordsTotal' => 2,
            'recordsFiltered' => 2
        ]);
    }

    public function test_outstanding_validRequestEmptyData_expectedData()
    {
        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start'    => 0,
            'length'   => 10,
        ];

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'data' => null,
            'recordsTotal' => 0,
            'recordsFiltered' => 0
        ]);
    }

    public function test_outstanding_validRequestVirtualSports_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetVirtualSportDetails" => [
                        [
                            "trans_id" => 282450939317583007,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => 3.4,
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
                                    "name" => "Virtual Soccer Asian Cup"
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
                            "settlement_time" => "2024-10-14T22:39:21.303",
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        DB::table('sab.reports')->insert([
            [
                'bet_id' => 'confirmBet-1-282450939317583007',
                'trx_id' => '282450939317583007',
                'play_id' => 'testPlayID',
                'web_id' => 27,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0,
                'bet_time' => '2024-07-10 12:23:25',
                'bet_choice' => '-',
                'game_code' => '1',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '',
                'odds' => 0,
                'result' => '-',
                'flag' => 'running',
                'status' => '1',
                'created_at' => '2024-07-10 12:23:25',
                'updated_at' => '2024-07-10 12:23:25',
                'ip_address' => '123.456.7.8',
            ]
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start'    => 0,
            'length'   => 10,
        ];

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'data' => [
                [
                    'id' => '282450939317583007',
                    'bet_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'game_type' => 'Handicap',
                    'league' => 'Virtual Soccer Asian Cup',
                    'match' => 'Netherlands vs Portugal',
                    'bet_option' => 'Netherlands',
                    'amount' => '100.000000',
                    'hdp' => '3.4',
                    'odds' => '1.24',
                    'odds_type' => 'Decimal Odds',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => 'Handicap',
                    'bet_choice' => 'Netherlands',
                    'bet_type' => 'Handicap',
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);

        $this->assertTrue(
            Str::contains(
                $response->getContent(),
                'visual'
            )
        );
    }

    public function test_outstanding_validRequestNumberGame_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetNumberDetails" => [
                        [
                            "trans_id" => 282450939317583007,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "sport_type" => 161,
                            "bet_type" => 90,
                            "settlement_time" => "2024-10-14T22:39:21.303",
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        DB::table('sab.reports')->insert([
            [
                'bet_id' => 'confirmBet-1-282450939317583007',
                'trx_id' => '282450939317583007',
                'play_id' => 'testPlayID',
                'web_id' => 27,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0,
                'bet_time' => '2024-07-10 12:23:25',
                'bet_choice' => '-',
                'game_code' => '90',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '',
                'odds' => 0,
                'result' => '-',
                'flag' => 'running',
                'status' => '1',
                'created_at' => '2024-07-10 12:23:25',
                'updated_at' => '2024-07-10 12:23:25',
                'ip_address' => '123.456.7.8',
            ],
            [
                'bet_id' => 'confirmBet-1-282450939317583008',
                'trx_id' => '282450939317583008',
                'play_id' => 'testPlayID2',
                'web_id' => 27,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0,
                'bet_time' => '2024-07-10 12:23:25',
                'bet_choice' => '-',
                'game_code' => '90',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '',
                'odds' => 0,
                'result' => '-',
                'flag' => 'settled',
                'status' => '1',
                'created_at' => '2021-06-02 12:23:25',
                'updated_at' => '2024-07-10 12:23:25',
                'ip_address' => '123.456.7.8',
            ]
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start'    => 0,
            'length'   => 10,
        ];

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'data' => [
                [
                    'id' => '282450939317583007',
                    'bet_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'game_type' => '-',
                    'league' => '-',
                    'match' => '-',
                    'bet_option' => '-',
                    'amount' => '100.000000',
                    'hdp' => '-',
                    'odds' => '1.24',
                    'odds_type' => 'Decimal Odds',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);

        $this->assertTrue(
            Str::contains(
                $response->getContent(),
                'visual'
            )
        );
    }

    public function test_outstanding_validRequestOutright_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 282450939317583007,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "hdp" => null,
                            "home_score" => null,
                            "away_score" => null,
                            "sport_type" => 1,
                            "bet_type" => 10,
                            "ParlayData" => null,
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

        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        DB::table('sab.reports')->insert([
            [
                'bet_id' => 'confirmBet-1-282450939317583007',
                'trx_id' => '282450939317583007',
                'play_id' => 'testPlayID',
                'web_id' => 27,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0,
                'bet_time' => '2024-07-10 12:23:25',
                'bet_choice' => '-',
                'game_code' => '10',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '',
                'odds' => 0,
                'result' => '-',
                'flag' => 'running',
                'status' => '1',
                'created_at' => '2024-07-10 12:23:25',
                'updated_at' => '2024-07-10 12:23:25',
                'ip_address' => '123.456.7.8',
            ],
            [
                'bet_id' => 'confirmBet-1-282450939317583008',
                'trx_id' => '282450939317583008',
                'play_id' => 'testPlayID2',
                'web_id' => 27,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0,
                'bet_time' => '2024-07-10 12:23:25',
                'bet_choice' => '-',
                'game_code' => '10',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '',
                'odds' => 0,
                'result' => '-',
                'flag' => 'settled',
                'status' => '1',
                'created_at' => '2021-06-02 12:23:25',
                'updated_at' => '2024-07-10 12:23:25',
                'ip_address' => '123.456.7.8',
            ]
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start'    => 0,
            'length'   => 10,
        ];

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'data' => [
                [
                    'id' => '282450939317583007',
                    'bet_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'game_type' => 'Outright',
                    'league' => '2024/2025 UEFA CHAMPIONS LEAGUE - TOP GOALSCORER',
                    'match' => '-',
                    'bet_option' => '-',
                    'amount' => '100.000000',
                    'hdp' => '-',
                    'odds' => '1.24',
                    'odds_type' => 'Decimal Odds',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => 'Outright',
                    'bet_choice' => '-',
                    'bet_type' => 'Outright',
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);

        $this->assertTrue(
            Str::contains(
                $response->getContent(),
                'visual'
            )
        );
    }

    public function test_outstanding_betNotFoundThirdPartyException_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 1,
            ]))
        ]);

        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        DB::table('sab.reports')->insert([
            [
                'bet_id' => 'confirmBet-1-282450939317583007',
                'trx_id' => '282450939317583007',
                'play_id' => 'testPlayID',
                'web_id' => 27,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0,
                'bet_time' => '2024-07-10 12:23:25',
                'bet_choice' => '-',
                'game_code' => '0',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '',
                'odds' => 0,
                'result' => '-',
                'flag' => 'running',
                'status' => '1',
                'created_at' => '2024-07-10 12:23:25',
                'updated_at' => '2024-07-10 12:23:25',
                'ip_address' => '123.456.7.8',
            ]
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start'    => 0,
            'length'   => 10,
        ];

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'data' => [
                [
                    'id' => '282450939317583007',
                    'bet_id' => '282450939317583007',
                    'play_id' => 'testPlayID',
                    'game_type' => '-',
                    'league' => '-',
                    'match' => '-',
                    'bet_option' => '-',
                    'amount' => '100.000000',
                    'hdp' => '-',
                    'odds' => '-',
                    'odds_type' => '-',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '123.456.7.8',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'sports_type' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);
    }

    /**
     * @dataProvider outstandingParams
     */
    public function test_outstanding_incompleteRequest_expectedData($param)
    {
        $request = [
            'currency' => 'IDR',
            'branchId' => 27,
            'start' => 0,
            'length' => 10,
        ];

        unset($request[$param]);

        $response = $this->post('/sab/sportsbooks/outstanding', $request);

        $response->assertJson([
            'code' => 422,
            'data' => NULL,
            'error' => 'invalid request format',
        ]);

        $response->assertStatus(200);
    }

    public static function outstandingParams()
    {
        return [
            ['currency'],
            ['branchId'],
            ['start'],
            ['length']
        ];
    }
}
