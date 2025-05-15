<?php

use App\Models\SboPlayer;
use Tests\TestCase;
use App\Models\SboReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SboOutstandingTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_outstanding_validRequest_expected()
    {
        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'betOption' => 'e-Spain',
                                'marketType' => 'Handicap',
                                'sportType' => 'Football',
                                'hdp' => -0.25,
                                'odds' => -1.42,
                                'league' => 'e-Football F23 International Friendly',
                                'match' => 'e-Spain vs e-Italy',
                                'liveScore' => '0:0',
                                'htScore' => '5:1',
                                'ftScore' => '5:1',
                            ]
                        ],
                        'sportsType' => 'Football',
                        'oddsStyle' => 'I',
                    ]
                ],
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
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
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID2',
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
            'flag' => 'settled',
            'status' => '1',
            'created_at' => '2021-06-02 12:23:25',
            'updated_at' => '2024-07-10 12:23:25',
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'currency' => 'IDR',
            // 'currency' => null,
            'branchId' => [27],
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        $response = $this->post('/sbo/sportsbooks/outstanding', $request);

        $response->assertJson([
            'draw' => 1,
            'data' => [
                [
                    'id' => 'testTransactionID',
                    'bet_id' => 'testTransactionID',
                    'branch_id' => 27,
                    'play_id' => 'testPlayID',
                    'sports_type' => 'Football',
                    'game_type' => 'Handicap',
                    'league' => 'e-Football F23 International Friendly',
                    'match' => 'e-Spain vs e-Italy',
                    'bet_option' => 'e-Spain',
                    'bet_choice' => 'e-Spain',
                    'bet_type' => 'Handicap',
                    'amount' => '100.000000',
                    'hdp' => -0.25,
                    'odds' => -1.42,
                    'odds_type' => 'I',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '-',
                    'live_score' => '0:0',
                    'is_live' => true,
                    'ft_score' => '5:1',
                    'is_first_half' => 0,
                    'detail_link' => 'test-url',
                    'ht_score' => '5:1'
                ]
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);
    }

    public function test_outstanding_validRequestParlayData_expected()
    {
        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'betOption' => 'e-England',
                                'marketType' => 'Handicap',
                                'sportType' => 'Football',
                                'hdp' => 0.00,
                                'odds' => 1.66,
                                'league' => 'e-Football F23 International Friendly',
                                'match' => 'e-England vs e-Portugal',
                                'liveScore' => '1:0',
                                'htScore' => '1:0',
                                'ftScore' => '2:0',
                            ],
                        ],
                        'sportsType' => 'Mix Parlay',
                        'odds' => 6.234,
                        'oddsStyle' => 'E',
                        'isLive' => true,
                    ]
                ],
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
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
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => [27],
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        $response = $this->post('/sbo/sportsbooks/outstanding', $request);

        $response->assertJson([
            'draw' => 1,
            'data' => [
                [
                    'id' => 'testTransactionID',
                    'bet_id' => 'testTransactionID',
                    'branch_id' => 27,
                    'play_id' => 'testPlayID',
                    'sports_type' => 'Mix Parlay',
                    'game_type' => '-',
                    'league' => '-',
                    'match' => 'Mix Parlay',
                    'bet_option' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                    'amount' => '100.000000',
                    'hdp' => 0,
                    'odds' => 6.234,
                    'odds_type' => 'E',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '-',
                    'live_score' => '1:0',
                    'is_live' => true,
                    'ft_score' => '2:0',
                    'is_first_half' => 0,
                    'detail_link' => 'test-url',
                    'ht_score' => '1:0'
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);
    }

    public function test_outstanding_validRequestVirtualSports_expected()
    {
        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'gameId' => 2694172,
                        'odds' => 3.450,
                        'oddsStyle' => 'Euro',
                        'productType' => 'VirtualFootballDesktop',
                        'subBet' => [
                            [
                                'htScore' => '0:1',
                                'ftScore' => '0:3',
                                'betOption' => 'VL Athens',
                                'marketType' => '1X2',
                                'hdp' => '0',
                                'odds' => 3.450,
                                'match' => 'VL Athens -vs- VL London',
                            ]
                        ],
                    ]
                ],
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
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
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => [27],
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        $response = $this->post('/sbo/sportsbooks/outstanding', $request);

        $response->assertJson([
            'draw' => 1,
            'data' => [
                [
                    'id' => 'testTransactionID',
                    'bet_id' => 'testTransactionID',
                    'play_id' => 'testPlayID',
                    'sports_type' => 'Virtual Sports',
                    'game_type' => '1X2',
                    'league' => '-',
                    'match' => 'VL Athens -vs- VL London',
                    'bet_option' => 'VL Athens',
                    'bet_choice' => 'VL Athens',
                    'bet_type' => '1X2',
                    'amount' => '100.000000',
                    'hdp' => '0',
                    'odds' => 3.45,
                    'odds_type' => 'E',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '-',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '0:3',
                    'is_first_half' => 0,
                    'detail_link' => 'test-url',
                    'ht_score' => '0:1'
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);
    }

    public function test_outstanding_validRequestVirtualSportsParlayData_expected()
    {
        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'odds' => 18.400,
                        'oddsStyle' => 'Euro',
                        'productType' => 'MixParlayDesktop',
                        'subBet' => [
                            [
                                'htScore' => '1:1',
                                'ftScore' => '1:1',
                                'betOption' => 'VL Ankara',
                                'marketType' => '1X2',
                                'hdp' => '0',
                                'odds' => 5.000,
                                'match' => 'VL Ankara -vs- VL Berlin',
                                'status' => 'Lose',
                                'winLostDate' => '2024-07-21T00:00:00.000'
                            ],
                            [
                                'htScore' => '0:0',
                                'ftScore' => '0:0',
                                'betOption' => 'Over',
                                'marketType' => 'Over/Under',
                                'hdp' => '1.5',
                                'odds' => 1.600,
                                'match' => 'VL Lisbon -vs- VL Zagreb',
                                'status' => 'Lose',
                                'winLostDate' => '2024-07-21T00:00:00.000'
                            ],
                        ],
                    ]
                ],
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
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
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => [27],
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        $response = $this->post('/sbo/sportsbooks/outstanding', $request);

        $response->assertJson([
            'draw' => 1,
            'data' => [
                [
                    'id' => 'testTransactionID',
                    'bet_id' => 'testTransactionID',
                    'play_id' => 'testPlayID',
                    'sports_type' => 'Mix Parlay',
                    'game_type' => '-',
                    'league' => '-',
                    'match' => 'Mix Parlay',
                    'bet_option' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                    'amount' => '100.000000',
                    'hdp' => '0',
                    'odds' => 18.4,
                    'odds_type' => 'E',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '-',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '1:1',
                    'is_first_half' => 0,
                    'detail_link' => 'test-url',
                    'ht_score' => '1:1'
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);
    }

    public function test_outstanding_validRequestRunningAndRollback_expected()
    {
        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'betOption' => 'e-Spain',
                                'marketType' => 'Handicap',
                                'sportType' => 'Football',
                                'hdp' => -0.25,
                                'odds' => -1.42,
                                'league' => 'e-Football F23 International Friendly',
                                'match' => 'e-Spain vs e-Italy',
                                'liveScore' => '0:0',
                                'htScore' => '5:1',
                                'ftScore' => '5:1',
                            ]
                        ],
                        'sportsType' => 'Football',
                        'oddsStyle' => 'I',
                    ]
                ],
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
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
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID2',
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
            'flag' => 'rollback',
            'status' => '1',
            'created_at' => '2024-07-10 12:23:25',
            'updated_at' => '2024-07-10 12:23:25',
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID2',
            'currency' => 'IDR'
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => [27],
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        $response = $this->post('/sbo/sportsbooks/outstanding', $request);

        $response->assertJson([
            'draw' => 1,
            'data' => [
                [
                    'id' => 'testTransactionID',
                    'bet_id' => 'testTransactionID',
                    'play_id' => 'testPlayID',
                    'sports_type' => 'Football',
                    'game_type' => 'Handicap',
                    'league' => 'e-Football F23 International Friendly',
                    'match' => 'e-Spain vs e-Italy',
                    'bet_option' => 'e-Spain',
                    'bet_choice' => 'e-Spain',
                    'bet_type' => 'Handicap',
                    'amount' => '100.000000',
                    'hdp' => -0.25,
                    'odds' => -1.42,
                    'odds_type' => 'I',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '-',
                    'live_score' => '0:0',
                    'is_live' => true,
                    'ft_score' => '5:1',
                    'is_first_half' => 0,
                    'detail_link' => 'test-url',
                    'ht_score' => '5:1'
                ],
                [
                    'id' => 'testTransactionID2',
                    'bet_id' => 'testTransactionID2',
                    'play_id' => 'testPlayID2',
                    'sports_type' => 'Football',
                    'game_type' => 'Handicap',
                    'league' => 'e-Football F23 International Friendly',
                    'match' => 'e-Spain vs e-Italy',
                    'bet_option' => 'e-Spain',
                    'bet_choice' => 'e-Spain',
                    'bet_type' => 'Handicap',
                    'amount' => '100.000000',
                    'hdp' => -0.25,
                    'odds' => -1.42,
                    'odds_type' => 'I',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '-',
                    'live_score' => '0:0',
                    'is_live' => true,
                    'ft_score' => '5:1',
                    'is_first_half' => 0,
                    'detail_link' => 'test-url',
                    'ht_score' => '5:1'
                ],
            ],
            'recordsTotal' => 2,
            'recordsFiltered' => 2
        ]);
    }

    public function test_outstanding_validRequestEmptyData_expected()
    {
        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0,
            'bet_time' => '2023-07-09 00:00:00',
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
            'created_at' => '2024-07-09 00:00:00',
            'updated_at' => '2024-07-09 00:00:00',
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => [27],
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        $response = $this->post('/sbo/sportsbooks/outstanding', $request);

        $response->assertJson([
            'draw' => 1,
            'data' => null,
            'recordsTotal' => 0,
            'recordsFiltered' => 0
        ]);
    }

    public function test_outstanding_validRequestMultipleBranches_expected()
    {
        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'betOption' => 'e-Spain',
                                'marketType' => 'Handicap',
                                'sportType' => 'Football',
                                'hdp' => -0.25,
                                'odds' => -1.42,
                                'league' => 'e-Football F23 International Friendly',
                                'match' => 'e-Spain vs e-Italy',
                                'liveScore' => '0:0',
                                'htScore' => '5:1',
                                'ftScore' => '5:1',
                            ]
                        ],
                        'sportsType' => 'Football',
                        'oddsStyle' => 'I',
                    ]
                ],
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        Http::fake([
            '/web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]))
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 29,
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
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID2',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 1000.00,
            'payout_amount' => 0,
            'bet_time' => '2024-07-10 12:12:25',
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
            'created_at' => '2024-07-10 09:23:25',
            'updated_at' => '2024-07-10 12:23:25',
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID3',
            'trx_id' => 'testTransactionID3',
            'play_id' => 'testPlayID3',
            'web_id' => 10,
            'currency' => 'IDR',
            'bet_amount' => 1000.00,
            'payout_amount' => 0,
            'bet_time' => '2024-07-10 12:12:25',
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
            'created_at' => '2024-07-10 09:23:25',
            'updated_at' => '2024-07-10 12:23:25',
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID2',
            'currency' => 'IDR'
        ]);

        $request = [
            'currency' => 'IDR',
            // 'currency' => null,
            'branchId' => [27, 29],
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        $response = $this->post('/sbo/sportsbooks/outstanding', $request);

        $response->assertJson([
            'draw' => 1,
            'data' => [
                [
                    'id' => 'testTransactionID',
                    'bet_id' => 'testTransactionID',
                    'branch_id' => 29,
                    'play_id' => 'testPlayID',
                    'game_type' => 'Handicap',
                    'league' => 'e-Football F23 International Friendly',
                    'match' => 'e-Spain vs e-Italy',
                    'bet_option' => 'e-Spain',
                    'amount' => '100.000000',
                    'hdp' => '-0.25',
                    'odds' => '-1.42',
                    'odds_type' => 'I',
                    'sports_type' => 'Football',
                    'bet_choice' => 'e-Spain',
                    'bet_type' => 'Handicap',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '-',
                    'live_score' => '0:0',
                    'is_live' => '-',
                    'ft_score' => '5:1',
                    'is_first_half' => 0,
                    'detail_link' => 'test-url',
                    'ht_score' => '5:1'
                ],
                [
                    'id' => 'testTransactionID2',
                    'bet_id' => 'testTransactionID2',
                    'branch_id' => 27,
                    'play_id' => 'testPlayID2',
                    'game_type' => 'Handicap',
                    'league' => 'e-Football F23 International Friendly',
                    'match' => 'e-Spain vs e-Italy',
                    'bet_option' => 'e-Spain',
                    'amount' => '1000.000000',
                    'hdp' => '-0.25',
                    'odds' => '-1.42',
                    'odds_type' => 'I',
                    'sports_type' => 'Football',
                    'bet_choice' => 'e-Spain',
                    'bet_type' => 'Handicap',
                    'bet_time' => '2024-07-10 12:12:25',
                    'bet_ip' => '-',
                    'live_score' => '0:0',
                    'is_live' => '-',
                    'ft_score' => '5:1',
                    'is_first_half' => 0,
                    'detail_link' => 'test-url',
                    'ht_score' => '5:1'
                ]
            ],
            'recordsTotal' => 2,
            'recordsFiltered' => 2
        ]);
    }

    public function test_outstanding_betNotFoundThirdPartyException_expected()
    {
        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::sequence()
                ->push(json_encode([
                    'result' => [
                        [
                            'subBet' => [
                                [
                                    'betOption' => 'e-Spain',
                                    'marketType' => 'Handicap',
                                    'sportType' => 'Football',
                                    'hdp' => -0.25,
                                    'odds' => -1.42,
                                    'league' => 'e-Football F23 International Friendly',
                                    'match' => 'e-Spain vs e-Italy',
                                    'liveScore' => '0:0',
                                    'htScore' => '5:1',
                                    'ftScore' => '5:1',
                                ]
                            ],
                            'sportsType' => 'Football',
                            'oddsStyle' => 'I',
                        ]
                    ],
                    'error' => [
                        'id' => 0,
                    ]
                ]))
                ->push(json_encode([
                    'error' => [
                        'id' => 1,
                    ]
                ])),
        ]);

        // Separate fake for get-bet-payload.aspx
        Http::fake([
            'web-root/restricted/report/get-bet-payload.aspx' => Http::response(json_encode([
                'url' => 'test-url',
                'error' => [
                    'id' => 0,
                ]
            ]), 200),
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 29,
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
        ]);

        SboReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID2',
            'trx_id' => 'testTransactionID2',
            'play_id' => 'testPlayID2',
            'web_id' => 27,
            'currency' => 'IDR',
            'bet_amount' => 1000.00,
            'payout_amount' => 0,
            'bet_time' => '2024-07-10 12:12:25',
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
            'created_at' => '2024-07-10 09:23:25',
            'updated_at' => '2024-07-10 12:23:25',
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SboPlayer::factory()->create([
            'play_id' => 'testPlayID2',
            'currency' => 'IDR'
        ]);

        $request = [
            'currency' => 'IDR',
            'branchId' => [27, 29],
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        $response = $this->post('/sbo/sportsbooks/outstanding', $request);

        $response->assertJson([
            'draw' => 1,
            'data' => [
                [
                    'id' => 'testTransactionID',
                    'bet_id' => 'testTransactionID',
                    'branch_id' => 29,
                    'play_id' => 'testPlayID',
                    'game_type' => 'Handicap',
                    'league' => 'e-Football F23 International Friendly',
                    'match' => 'e-Spain vs e-Italy',
                    'bet_option' => 'e-Spain',
                    'amount' => '100.000000',
                    'hdp' => '-0.25',
                    'odds' => '-1.42',
                    'odds_type' => 'I',
                    'sports_type' => 'Football',
                    'bet_choice' => 'e-Spain',
                    'bet_type' => 'Handicap',
                    'bet_time' => '2024-07-10 12:23:25',
                    'bet_ip' => '-',
                    'live_score' => '0:0',
                    'is_live' => '-',
                    'ft_score' => '5:1',
                    'is_first_half' => 0,
                    'detail_link' => 'test-url',
                    'ht_score' => '5:1'
                ],
            ],
            'recordsTotal' => 1,
            'recordsFiltered' => 1
        ]);
    }

    /**
     * @dataProvider outstandingParams
     */
    public function test_outstanding_incompleteRequest_expected($param)
    {
        $request = [
            'currency' => 'IDR',
            'branchId' => [27],
            'draw' => 1,
            'start' => 0,
            'length' => 10,
        ];

        unset($request[$param]);

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);

        $response->assertStatus(200);
    }

    public static function outstandingParams()
    {
        return [
            ['currency'],
            ['branchId'],
            ['draw'],
            ['start'],
            ['length']
        ];
    }
}
