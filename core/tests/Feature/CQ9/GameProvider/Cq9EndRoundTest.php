<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Cq9Player;
use App\Models\Cq9Report;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class Cq9EndRoundTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE cq9.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE cq9.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_endRound_validRequest_expectedData()
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
                                'credit' => 1000.00
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 1150.0
                            ];
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
                    };
                }
            };
        });

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null,
        ]);

        $request = [
            'account' => 'testPlayID',
            'gamehall' => 'cq9',
            'gamecode' => 'testGameCode',
            'roundid' => 'testTransactionID',
            'data' => json_encode([
                [
                    "amount" => 100.00,
                ],
                [
                    "amount" => 50.00,
                ],
            ]),
            'createTime' => '2021-01-01T00:00:00.000-04:00'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/endround', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => [
                'balance' => 1150.0,
                'currency' => 'IDR'
            ],
            'status' => [
                'code' => '0',
                'message' => 'Success',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cq9.reports', [
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 150.00,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 12:00:00'
        ]);
    }

    public function test_endRound_invalidCreateTimeEventTime_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null,
        ]);

        $request = [
            'account' => 'testPlayID',
            'gamehall' => 'cq9',
            'gamecode' => 'testGameCode',
            'roundid' => 'testTransactionID',
            'data' => json_encode([
                [
                    "amount" => 100.00,
                    "eventtime" => "2021-01-01 00:00:00.1234567890-00:00"
                ],
            ]),
            'createTime' => '2021-01-01T00:00:00.123456789-00:00'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/endround', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '1004',
                'message' => 'Time Format error.',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_endRound_invalidWToken_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'testPlayID',
            'gamehall' => 'cq9',
            'gamecode' => 'testGameCode',
            'roundid' => 'testTransactionID',
            'data' => json_encode([
                [
                    "amount" => 100.00,
                ],
                [
                    "amount" => 50.00,
                ],
            ]),
            'createTime' => '2021-01-01T00:00:00.000-04:00'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/endround', $request, [
            'wtoken' => 'invalidWToken'
        ]);

        $response->assertJson([
            'data' => false,
            'status' => [
                'code' => '3',
                'message' => 'Token invalid.',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_endRound_playerNotFound_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'nonexistentPlayID',
            'gamehall' => 'cq9',
            'gamecode' => 'testGameCode',
            'roundid' => 'testTransactionID',
            'data' => json_encode([
                [
                    "amount" => 100.00,
                ],
                [
                    "amount" => 50.00,
                ],
            ]),
            'createTime' => '2021-01-01T00:00:00.000-04:00'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/endround', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '1006',
                'message' => 'Player not found.',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_endRound_transactionAlreadySettled_expectedData()
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
                                'credit' => 1000.00
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 1150.0
                            ];
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
                    };
                }
            };
        });

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID',
            'updated_at' => '2021-01-01 12:00:00',
        ]);

        $request = [
            'account' => 'testPlayID',
            'gamehall' => 'cq9',
            'gamecode' => 'testGameCode',
            'roundid' => 'testTransactionID',
            'data' => json_encode([
                [
                    "amount" => 100.00,
                ],
                [
                    "amount" => 50.00,
                ],
            ]),
            'createTime' => '2021-01-01T00:00:00.000-04:00'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/endround', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '100',
                'message' => 'Something wrong.',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_endRound_emptyWalletResponse_expectedData()
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
                    };
                }
            };
        });

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID',
            'win_amount' => 0,
            'updated_at' => null,
        ]);

        $request = [
            'account' => 'testPlayID',
            'gamehall' => 'cq9',
            'gamecode' => 'testGameCode',
            'roundid' => 'testTransactionID',
            'data' => json_encode([
                [
                    "amount" => 100.00,
                ],
                [
                    "amount" => 50.00,
                ],
            ]),
            'createTime' => '2021-01-01T00:00:00.000-04:00'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/endround', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '1100',
                'message' => 'Server error.',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider endRoundParams
     */
    public function test_endRound_incompleteParam_expectedData($param)
    {
        $request = [
            'account' => 'testPlayID',
            'gamehall' => 'cq9',
            'gamecode' => 'testGameCode',
            'roundid' => 'testTransactionID',
            'data' => json_encode([
                [
                    "amount" => 100.00,
                ],
                [
                    "amount" => 50.00,
                ],
            ]),
            'createTime' => '2021-01-01T00:00:00.000-04:00'
        ];

        unset($request[$param]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/endround', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '1003',
                'message' => 'Parameter error.',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);
    }

    public static function endRoundParams()
    {
        return [
            ['account'],
            ['gamehall'],
            ['gamecode'],
            ['roundid'],
            ['data'],
            ['createTime'],
        ];
    }
}
