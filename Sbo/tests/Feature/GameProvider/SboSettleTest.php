<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SboSettleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_settle_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2200.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'sportType' => 'Football',
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

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
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
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 2200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
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
            'status' => 1
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
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
            'status' => 0
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 1200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1
        ]);
    }

    public function test_settle_validRequestParlay_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2200.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

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

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
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
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 2200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
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
            'status' => 1
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
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
            'status' => 0
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100,
            'payout_amount' => 1200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => '-',
            'game_code' => 'Mix Parlay',
            'sports_type' => 'Mix Parlay',
            'event' => '-',
            'match' => 'Mix Parlay',
            'hdp' => '0',
            'odds' => 5.70,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1
        ]);
    }

    #[DataProvider('resultParams')]
    public function test_settle_validRequestMultipleResult_expectedData($bet, $payout, $result, $isCashOut)
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2200.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'sportType' => 'Football',
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

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => $bet,
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
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => $payout,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => $isCashOut
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 2200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => $bet,
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
            'status' => 1
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => $bet,
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
            'status' => 0
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => $bet,
            'payout_amount' => $payout,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => $result,
            'flag' => 'settled',
            'status' => 1
        ]);
    }

    public static function resultParams()
    {
        return [
            [1000.00, 5000.00, 'win', false],
            [1000.00, 1000.00, 'draw', false],
            [1000.00, 900.00, 'cash out', true],
            [1000.00, 0.00, 'lose', false]
        ];
    }

    #[DataProvider('betOptionParams')]
    public function test_settle_validRequestMultipleBetOption_expectedData($betOption, $match)
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 2200.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'sportType' => 'Football',
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

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
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
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 2200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
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
            'status' => 1
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
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
            'status' => 0
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'payout-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.0,
            'payout_amount' => 1200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => $match,
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1
        ]);
    }

    public static function betOptionParams()
    {
        return [
            [1, 'Denmark'],
            ['Over', 'Over'],
            [2, 'England'],
            ['Under', 'Under'],
            ['draw', 'draw'],
            ['X', 'draw'],
        ];
    }

    public function test_settle_validRequestFlagRollback_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'credit_after' => 2200.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'sportType' => 'Football',
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

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'rollback-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.0,
                'payout_amount' => 200.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'rollback',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'AccountName' => 'testPlayID',
            'Balance' => 2200.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.0,
            'payout_amount' => 200.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'rollback',
            'status' => 1
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'rollback-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.0,
            'payout_amount' => 200.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'rollback',
            'status' => 0
        ]);

        $this->assertDatabaseHas('sbo.reports', [
            'bet_id' => 'resettle-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100,
            'payout_amount' => 1200.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1
        ]);
    }

    #[DataProvider('settleParams')]
    public function test_settle_incompleteRequestParameter_expectedData($param)
    {
        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
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
            ['IsCashOut']
        ];
    }

    public function test_settle_playerNotFound_expectedData()
    {
        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'invalidPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_invalidCompanyKey_expectedData()
    {
        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
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
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'invalid-company-key',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_transactionNotFound_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2500.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
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
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'invalid_transactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 6,
            'ErrorMessage' => 'Bet not exists',
            'Balance' => 2500,
            'AccountName' => 'testPlayID',
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_transactionAlreadySettled_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2500.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'payout-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 1200.0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 2001,
            'ErrorMessage' => 'Bet Already Settled',
            'Balance' => 2500,
            'AccountName' => 'testPlayID',
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_transactionAlreadyVoid_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 2500.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'cancel-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'void',
                'flag' => 'void',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 2002,
            'ErrorMessage' => 'Bet Already Cancelled',
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_walletErrorBalance_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 999
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'payout-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000,
                'payout_amount' => 1200.0,
                'bet_time' => '2020-01-02 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'settled',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_getBetListErrorIdNot0_expectedData()
    {
        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'error' => [
                    'id' => 1
                ]
            ]))
        ]);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
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
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('getBetListParam')]
    public function test_settle_missingThirdPartyApiResponseParameter_expectedData($param)
    {
        $apiResponse = [
            'result' => [],
            'error' => [
                'id' => 0
            ]
        ];

        unset($apiResponse[$param]);

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode($apiResponse))
        ]);

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
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
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    public static function getBetListParam()
    {
        return [
            ['result'],
            ['error'],
            ['id']
        ];
    }

    public function test_settle_walletErrorResettle_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function resettle(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, string $betID, string $settledTransactionID, string $betTime): array
            {
                return [
                    'status_code' => 999
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'sportType' => 'Football',
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

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'rollback-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 100.0,
                'payout_amount' => 200.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => 'Over',
                'game_code' => 'Money Line',
                'sports_type' => 'Football',
                'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
                'match' => 'Denmark-vs-England',
                'hdp' => '2.5',
                'odds' => 3.40,
                'result' => 'win',
                'flag' => 'rollback',
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ];

        $response = $this->post('/sbo/prov/Settle', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sbo.reports', [
            'bet_id' => 'resettle-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100,
            'payout_amount' => 1200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1
        ]);
    }

    public function test_settle_walletErrorPayout_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'status_code' => 999
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            'web-root/restricted/report/get-bet-list-by-refnos.aspx' => Http::response(json_encode([
                'result' => [
                    [
                        'subBet' => [
                            [
                                'match' => 'Denmark-vs-England',
                                'marketType' => 'Money Line',
                                'sportType' => 'Football',
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

        DB::table('sbo.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
                'ip_address' => '123.456.7.8'
            ]);

        DB::table('sbo.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
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
                'status' => 1
            ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
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
            'bet_amount' => 1000,
            'payout_amount' => 1200.0,
            'bet_time' => '2020-01-02 12:00:00',
            'bet_choice' => 'Over',
            'game_code' => 'Money Line',
            'sports_type' => 'Football',
            'event' => 'ITF - Taipei W35 - Womens Singles (Set Handicap)',
            'match' => 'Denmark-vs-England',
            'hdp' => '2.5',
            'odds' => 3.40,
            'result' => 'win',
            'flag' => 'settled',
            'status' => 1
        ]);
    }
}
