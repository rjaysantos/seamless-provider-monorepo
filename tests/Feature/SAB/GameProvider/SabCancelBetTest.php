<?php

use Tests\TestCase;
use App\Models\SabPlayer;
use App\Models\SabReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class SabCancelBetTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_cancelBet_validRequest_expectedData()
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

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'flag' => 'waiting',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
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
            'balance' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => "cancelBet-1-testTransactionID",
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);
    }

    /**
     * @dataProvider oneToOneCurrency
     */
    public function test_cancelBet_validRequestOneToOneCurrency_expectedData($currency)
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

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'flag' => 'waiting',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
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
            'balance' => 1000.0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => "cancelBet-1-testTransactionID",
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => '1',
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
    public function test_cancelBet_validRequestOneToOneThousandCurrency_expectedData($currency)
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

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'bet_id' => 'W-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'flag' => 'waiting',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
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
            'balance' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => "cancelBet-1-testTransactionID",
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => '1',
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
    public function test_cancelBet_prodValidRequestOneToOneCurrency_expectedData($currency)
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

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'flag' => 'waiting',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => 'oe880dd8en',
            'message' => [
                'userId' => 'testPlayID',
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
            'balance' => 1000.0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => "cancelBet-1-testTransactionID",
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => '1',
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
    public function test_cancelBet_prodValidRequestOneToOneThousandCurrency_expectedData($currency)
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

        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'flag' => 'waiting',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);

        $request = [
            'key' => 'oe880dd8en',
            'message' => [
                'userId' => 'testPlayID',
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
            'balance' => 1
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => "cancelBet-1-testTransactionID",
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'h',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => '-',
            'flag' => 'cancelled',
            'status' => '1',
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

    public function test_cancelBet_invalidKey_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'status' => 1,
        ]);

        $request = [
            'key' => 'invalid_vendor_id',
            'message' => [
                'userId' => 'testPlayID',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_playerNotFound_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'nonexistentPlayID',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_transactionNotFound_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'flag' => 'waiting',
            'status' => 1,
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'nonexistentTransactionID',
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 504,
            'msg' => 'No Such Ticket'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_transactionNotWaiting_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'flag' => 'running',
            'status' => 1,
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 309,
            'msg' => 'Invalid Transaction Status',
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBet_emptyWalletResponse_expectedData()
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
                            return 0.0;
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
                            return null;
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

        SabReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'flag' => 'waiting',
            'status' => 1,
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider cancelBetParams
     */
    public function test_cancelBet_incompleteParameter_expectedData($param)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                    ]
                ]
            ]
        ];

        unset($request[$param]);

        $response = $this->post('/sab/prov/cancelbet', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function cancelBetParams()
    {
        return [
            ['key'],
            ['message']
        ];
    }
}
