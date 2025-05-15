<?php

use Tests\TestCase;
use App\Models\SabPlayer;
use App\Models\SabReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class SabPlaceBetParlayTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_placeBetParlay_validRequest_expectedData()
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID',
                    'licenseeTxId' => 'testTransactionID'
                ]
            ]
        ]);

        $response->assertStatus(200);

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
    }

    /**
     * @dataProvider oneToOneCurrency
     */
    public function test_placeBetParlay_validRequestOneToOneCurrency_expectedData($currency)
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID',
                    'licenseeTxId' => 'testTransactionID'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1,
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
    public function test_placeBetParlay_validRequestOneToOneThousandCurrency_expectedData($currency)
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID',
                    'licenseeTxId' => 'testTransactionID'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
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
    public function test_placeBetParlay_prodValidRequestOneToOneCurrency_expectedData($currency)
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

        $request = [
            'key' => 'oe880dd8en',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID',
                    'licenseeTxId' => 'testTransactionID'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 1,
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
    public function test_placeBetParlay_prodValidRequestOneToOneThousandCurrency_expectedData($currency)
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

        $request = [
            'key' => 'oe880dd8en',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 0,
            'txns' => [
                [
                    'refId' => 'testTransactionID',
                    'licenseeTxId' => 'testTransactionID'
                ]
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'wager-1-testTransactionID',
            'trx_id' => 'testTransactionID',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
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
    }

    public static function productionOneToOneThousandCurrency()
    {
        return [
            ['IDR'],
            ['VND'],
        ];
    }

    public function test_placeBetParlay_invalidKey_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'key' => 'invalid_vendorID',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 100.0,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBetParlay_playerNotFound_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'nonExistentPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 100.0,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBetParlay_transactionAlreadyExist_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => 'testTransactionID',
            'status' => 1
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 100.0,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 1,
            'msg' => 'Duplicate Transaction'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBetParlay_insufficientFund_expectedData()
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
                                'credit' => 100.0,
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 200.0,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 502,
            'msg' => 'Player Has Insufficient Funds'
        ]);

        $response->assertStatus(200);
    }

    public function test_placeBetParlay_emptyWalletResponse_expectedData()
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
                            return null;
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

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 200.0,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider placeBetParlayParams
     */
    public function test_placeBetParlay_incompleteParameter_expectedData($param)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 200.0,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        unset($request[$param]);

        $response = $this->post('/sab/prov/placebetparlay', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function placeBetParlayParams()
    {
        return [
            ['key'],
            ['message']
        ];
    }
}
