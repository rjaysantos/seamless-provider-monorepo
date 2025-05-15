<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Crypt;
use App\Libraries\Wallet\V2\TestWallet;

class SabVisualHtmlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_visualHtml_validEncryptedTrxId_expectedData()
    {
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
                            "stake" => 100,
                            "settlement_time" => "2024-07-31T00:35:24.007",
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

        DB::table('sab.reports')
            ->insert([
                'bet_id' => '339482738748',
                'trx_id' => '339482738748',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
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

        $trxID = Crypt::encryptString('339482738748');

        $response = $this->get('/sab/in/visual/' . $trxID);

        $response->assertStatus(200);
        $response->assertViewIs('sab_visual');
        $response->assertViewHas('ticketID', 339482738748);
    }

    public function test_visualHtml_validParlayEncryptedTrxId_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 3,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "bet_team" => "h",
                            "stake" => 100,
                            "settlement_time" => "2024-07-31T00:35:24.007",
                            "bettypename" => [
                                [
                                    "name" => "System Parlay"
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
                                ],
                            ],
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => '339482738748',
                'trx_id' => '339482738748',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => '-',
                'game_code' => '1',
                'sports_type' => 'Mix Parlay',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 1.24,
                'result' => 'won',
                'flag' => 'settled',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $trxID = Crypt::encryptString('339482738748');

        $response = $this->get('/sab/in/visual/' . $trxID);

        $response->assertStatus(200);
        $response->assertViewIs('sab_visual');
        $response->assertViewHas('ticketID', 339482738748);
    }

    public function test_visualHtml_validNumberGameEncryptedTrxId_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetNumberDetails" => [
                        [
                            "trans_id" => 339482738748,
                            "odds" => 1.24,
                            "odds_type" => 4,
                            "sport_type" => 161,
                            "ticket_status" => "win",
                            "stake" => 10.00,
                            "bet_type" => 90,
                            "settlement_time" => "2024-10-14T22:39:21.303",
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => '339482738748',
                'trx_id' => '339482738748',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
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

        $trxID = Crypt::encryptString('339482738748');

        $response = $this->get('/sab/in/visual/' . $trxID);

        $response->assertStatus(200);
        $response->assertViewIs('sab_visual');
        $response->assertViewHas('ticketID', 339482738748);
    }

    public function test_visualHtml_validVirtualSportsEncryptedTrxId_expectedData()
    {
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
                            "home_score" => 1,
                            "away_score" => 0,
                            "sport_type" => 1,
                            "bet_type" => 1,
                            "ParlayData" => null,
                            "bet_team" => "h",
                            "ticket_status" => "win",
                            "stake" => 100,
                            "settlement_time" => "2024-07-31T00:35:24.007",
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
                        ]
                    ]
                ]
            ]))
        ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => '339482738748',
                'trx_id' => '339482738748',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
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

        $trxID = Crypt::encryptString('339482738748');

        $response = $this->get('/sab/in/visual/' . $trxID);

        $response->assertStatus(200);
        $response->assertViewIs('sab_visual');
        $response->assertViewHas('ticketID', 339482738748);
    }

    public function test_visualHtml_transactionNotFound_expectedData()
    {
        DB::table('sab.reports')
            ->insert([
                'bet_id' => '339482738748',
                'trx_id' => '339482738748',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
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

        $trxID = Crypt::encryptString('invalid-trx-id');

        $response = $this->get('/sab/in/visual/' . $trxID);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null,
        ]);

        $response->assertStatus(200);
    }

    public function test_visualHtml_thirdPartyApiError_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response('', 500)
        ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => '339482738748',
                'trx_id' => '339482738748',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
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

        $trxID = Crypt::encryptString('339482738748');

        $response = $this->get('/sab/in/visual/' . $trxID);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    public function test_visualHtml_thirdPartyInvalidApiResponseFormat_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 'invalid_code'
            ]))
        ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => '339482738748',
                'trx_id' => '339482738748',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
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

        $trxID = Crypt::encryptString('339482738748');

        $response = $this->get('/sab/in/visual/' . $trxID);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    public function test_visualHtml_thirdPartyApiResponseErrorCodeNot0_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 1
            ]))
        ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => '339482738748',
                'trx_id' => '339482738748',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Under',
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

        $trxID = Crypt::encryptString('339482738748');

        $response = $this->get('/sab/in/visual/' . $trxID);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }
}
