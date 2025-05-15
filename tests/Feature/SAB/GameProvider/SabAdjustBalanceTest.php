<?php

use Tests\TestCase;
use App\Models\SabPlayer;
use App\Models\SabReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class SabAdjustBalanceTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_adjustBalance_validRequestCredit_expectedData()
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
                            return [
                                'credit_after' => 1000
                            ];
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
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
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
            'bet_id' => 'bonus-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
        ]);
    }

    public function test_adjustBalance_validRequestDebit_expectedData()
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
                            return [
                                'credit_after' => 3000
                            ];
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
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 3,
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
            'bet_id' => 'bonus-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => -3000.0,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
        ]);
    }

    /**
     * @dataProvider oneToOneCurrency
     */
    public function test_adjustBalance_validRequestOneToOneCurrency_expectedData($currency)
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
                            return [
                                'credit_after' => 1000
                            ];
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
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
                'balanceInfo' => [
                    'creditAmount' => 1000,
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
            'bet_id' => 'bonus-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
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
    public function test_adjustBalance_validRequestOneToOneThousandCurrency_expectedData($currency)
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
                            return [
                                'credit_after' => 1000
                            ];
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
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
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
            'bet_id' => 'bonus-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
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
    public function test_adjustBalance_prodValidRequestOneToOneCurrency_expectedData($currency)
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
                            return [
                                'credit_after' => 1000.0
                            ];
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
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
                'balanceInfo' => [
                    'creditAmount' => 1000,
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
            'bet_id' => 'bonus-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
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
    public function test_adjustBalance_prodValidRequestOneToOneThousandCurrency_expectedData($currency)
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
                            return [
                                'credit_after' => 1000.0
                            ];
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
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
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
            'bet_id' => 'bonus-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => $currency,
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
        ]);
    }

    public static function productionOneToOneThousandCurrency()
    {
        return [
            ['IDR'],
            ['VND'],
        ];
    }

    public function test_adjustBalance_validRequestCreditWalletError_expectedData()
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
                            return null;
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
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'bonus-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 0,
            'payout_amount' => 1000,
            'bet_time' => '2020-01-02 00:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'bonus',
            'status' => 1,
        ]);
    }

    public function test_adjustBalance_invalidKey_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'key' => 'invalid_vendor_id',
            'message' => [
                'userId' => 'testPlayID',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 300.0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_adjustBalance_playerNotFound_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'invalidPlayID',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
                'balanceInfo' => [
                    'creditAmount' => 100.0,
                    'debitAmount' => 0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_adjustBalance_transactionAlreadyExists_expectedData()
    {
        SabPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        SabReport::factory()->create([
            'trx_id' => '12345',
            'status' => 1
        ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
                'balanceInfo' => [
                    'creditAmount' => 100.0,
                    'debitAmount' => 0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 1,
            'msg' => 'Duplicate Transaction'
        ]);

        $response->assertStatus(200);
    }

    public function test_adjustBalance_invalidGrpcWalletResponse_expectedData()
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
                            return null;
                        }

                        public function TransferOut($payload)
                        {
                            return null;
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
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
                'balanceInfo' => [
                    'creditAmount' => 100.0,
                    'debitAmount' => 0,
                ]
            ]
        ];

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => '12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 100.0,
            'payout_amount' => 0,
            'bet_time' => '2020-01-02 12:00:00',
            'game_code' => 1,
            'odds' => 0,
            'flag' => 'running',
            'status' => '0',
            'created_at' => '2020-01-02 12:00:00',
            'updated_at' => '2020-01-02 12:00:00'
        ]);
    }

    /**
     * @dataProvider adjustBalanceParams
     */
    public function test_adjustBalance_incompleteRequest_expectedData($params)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testPlayID',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 1,
                'balanceInfo' => [
                    'creditAmount' => 100.0,
                    'debitAmount' => 0,
                ]
            ]
        ];

        unset($request[$params]);

        $response = $this->post('sab/prov/adjustbalance', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function adjustBalanceParams()
    {
        return [
            ['key'],
            ['message']
        ];
    }
}
