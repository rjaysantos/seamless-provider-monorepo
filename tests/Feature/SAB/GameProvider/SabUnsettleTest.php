<?php

use Tests\TestCase;
use App\Models\SabPlayer;
use App\Models\SabReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SabUnsettleTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_unsettle_validRequestSingleBet_expectedData()
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
                            return [
                                'credit_after' => 400.0
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
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 300.00,
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
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
            'bet_id' => 'unsettle-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'lose',
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    /**
     * @dataProvider productionOneToOneCurrency
     */
    public function test_unsettle_prodValidRequestOneToOneCurrency_expectedData($currency)
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
                            return 4000.0;
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
                                'credit_after' => 1000.0
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
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
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
                        'txId' => 277980818960285706,
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
            'bet_id' => 'unsettle-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'lose',
            'flag' => 'running',
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
    public function test_unsettle_prodValidRequestOneToOneThousandCurrency_expectedData($currency)
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
                            return 4000.0;
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
                                'credit_after' => 1000.0
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
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
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
                        'txId' => 277980818960285706,
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
            'bet_id' => 'unsettle-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'lose',
            'flag' => 'running',
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

    public function test_unsettle_validRequestSingleBetWalletError_expectedData()
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
            'bet_id' => 'payout-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 300.00,
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/unsettle', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'unsettle-1-277980818960285706',
            'trx_id' => '277980818960285706',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => 'Netherlands',
            'game_code' => 'Handicap',
            'sports_type' => 'Soccer',
            'event' => 'SABA ELITE FRIENDLY Virtual PES 23 - PENALTY SHOOTOUTS',
            'match' => 'Netherlands vs Portugal',
            'hdp' => 3.4,
            'odds' => 1.24,
            'result' => 'lose',
            'flag' => 'running',
            'status' => 1,
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_unsettle_invalidKey_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'flag' => 'settled',
            'status' => 1
        ]);

        $request = [
            'key' => 'invalid_vendor_id',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/unsettle', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_unsettle_playerNotFound_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'flag' => 'settled',
            'status' => 1
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'nonExistingPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        $response = $this->post('sab/prov/unsettle', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_unsettle_transactionNotSettled_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '277980818960285706',
            'flag' => 'void',
            'status' => 1
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        $response = $this->post('sab/prov/unsettle', $request);

        $response->assertJson([
            'status' => 309,
            'msg' => 'Invalid Transaction Status',
        ]);

        $response->assertStatus(200);
    }

    public function test_unsettle_emptyWalletResponse_expectedData()
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
            'trx_id' => '277980818960285706',
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
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/unsettle', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider unsettleParams
     */
    public function test_unsettle_incompleteRequest_expectedData($params)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testPlayID',
                        'refId' => 'testTransactionID',
                        'txId' => 277980818960285706,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        unset($request[$params]);

        $response = $this->post('sab/prov/unsettle', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function unsettleParams()
    {
        return [
            ['key'],
            ['message']
        ];
    }
}
