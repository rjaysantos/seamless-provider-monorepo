<?php

use Tests\TestCase;
use App\Models\SabPlayer;
use App\Models\SabReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SabResettleTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_resettle_validRequestWin_expectedData()
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
                            return [
                                'credit_after' => 4000.00
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

        SabReport::factory()->create([
            'bet_id' => 'payout-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.00,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'win',
                        'payout' => 3,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'resettle-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

    public function test_resettle_validRequestLose_expectedData()
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
                                'credit' => 4000.0
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
                                'credit_after' => 1000.00
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

        SabReport::factory()->create([
            'bet_id' => 'payout-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-09-08 16:49:32',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'lose',
                        'payout' => 0.00,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'resettle-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.00,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

    public function test_resettle_validRequestVoidTicket_expectedData()
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
                            return [
                                'credit_after' => 900.00
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

        SabReport::factory()->create([
            'bet_id' => 'payout-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 200.00,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'void',
                        'payout' => 0.00,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'resettle-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0.00,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'void',
            'flag' => 'settled',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    /**
     * @dataProvider oneToOneCurrency
     */
    public function test_resettle_validRequestOneToOneCurrency_expectedData($currency)
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
                            return [
                                'credit_after' => 4000.00
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
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'bet_id' => 'payout-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.00,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'win',
                        'payout' => 3000,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'resettle-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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
    public function test_resettle_validRequestOneToOneThousandCurrency_expectedData($currency)
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
                            return [
                                'credit_after' => 4000.00
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
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'bet_id' => 'payout-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.00,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'win',
                        'payout' => 3,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'resettle-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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
    public function test_resettle_prodValidRequestOneToOneCurrency_expectedData($currency)
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
                                'credit_after' => 1300.00
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
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'bet_id' => 'payout-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.00,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

        $request = [
            'key' => 'oe880dd8en',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'win',
                        'payout' => 3000,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'resettle-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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
    public function test_resettle_prodValidRequestOneToOneThousandCurrency_expectedData($currency)
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
                                'credit_after' => 1300.00
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
            'currency' => $currency
        ]);

        SabReport::factory()->create([
            'bet_id' => 'payout-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0.00,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

        $request = [
            'key' => 'oe880dd8en',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'win',
                        'payout' => 3,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 0
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'resettle-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

    public static function productionOneToOneThousandCurrency()
    {
        return [
            ['IDR'],
            ['VND'],
        ];
    }

    public function test_resettle_validRequestWinWalletError_expectedData()
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
            'bet_id' => 'payout-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0.00,
            'bet_time' => '2021-01-01 00:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'win',
                        'payout' => 3,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'resettle-1-123456789',
            'trx_id' => '123456789',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 3000,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
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

    public function test_resettle_invalidKey_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '123456789',
            'status' => 1
        ]);

        $request = [
            'key' => 'invalidKey',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'lose',
                        'payout' => 0.00,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_playerNotFound_expectedData()
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
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'lose',
                        'payout' => 0.00,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_transactionNotFound_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '123456789',
            'status' => 1
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'invalidTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'lose',
                        'payout' => 0.00,
                        'txId' => 000000
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 504,
            'msg' => 'No Such Ticket'
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_transactionNotSettled_expectedData()
    {
        Http::fake([
            'api/GetBetDetailByTransID' => Http::response(json_encode([
                "error_code" => 0,
                "Data" => [
                    "BetDetails" => [
                        [
                            "odds_type" => 3,
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
            'trx_id' => '123456789',
            'flag' => 'running',
            'status' => 1
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'lose',
                        'payout' => 0.00,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 309,
            'msg' => 'Invalid Transaction Status'
        ]);

        $response->assertStatus(200);
    }

    public function test_resettle_emptyWalletResponse_expectedData()
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
            'trx_id' => '123456789',
            'flag' => 'settled',
            'status' => 1
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'lose',
                        'payout' => 0.00,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider resettleParams
     */
    public function test_resettle_incompleteParameters_expectedData($param)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'lose',
                        'payout' => 0.00,
                        'txId' => 123456789
                    ]
                ]
            ]
        ];

        unset($request[$param]);

        $response = $this->post('/sab/prov/resettle', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function resettleParams()
    {
        return [
            ['key'],
            ['message']
        ];
    }
}
