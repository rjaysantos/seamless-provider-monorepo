<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Cq9Player;
use App\Models\Cq9Report;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class Cq9DebitTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE cq9.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE cq9.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_debit_validRequest_expectedData()
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
                            return [
                                'credit_after' => 950.00
                            ];
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return [
                                'credit_after' => 950.00
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

        Carbon::setTestNow('2021-01-01 12:00:00');

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'gamecode' => 'testGameCode',
            'gamehall' => 'testGameHall',
            'roundid' => 'testRoundID',
            'amount' => 50.00,
            'mtcode' => 'testMtCode'
        ];

        $response = $this->post('/cq9/prov/transaction/game/debit', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => [
                'balance' => 950.0,
                'currency' => 'IDR'
            ],
            'status' => [
                'code' => '0',
                'message' => 'Success',
                'datetime' => '2021-01-01T00:00:00-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cq9.reports', [
            'trx_id' => 'testMtCode',
            'bet_amount' => 50.00,
            'win_amount' => 0.00,
            'created_at' => '2021-01-01 12:00:00',
            'updated_at' => '2021-01-01 12:00:00'
        ]);

        Carbon::setTestNow();
    }

    public function test_debit_invalidEventTime_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID',
        ]);

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01',
            'gamecode' => 'testGameCode',
            'gamehall' => 'testGameHall',
            'roundid' => 'testRoundID',
            'amount' => 50.00,
            'mtcode' => 'testMtCode'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/debit', $request, [
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

    public function test_debit_playerNotFound_expectedData()
    {
        Carbon::setTestNow('2021-01-01 12:00:00');

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'invalidPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'gamecode' => 'testGameCode',
            'gamehall' => 'testGameHall',
            'roundid' => 'testRoundID',
            'amount' => 50.00,
            'mtcode' => 'testMtCode'
        ];

        $response = $this->post('/cq9/prov/transaction/game/debit', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '1006',
                'message' => 'Player not found.',
                'datetime' => '2021-01-01T00:00:00-04:00'
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_debit_transactionAlreadyExist_expectedData()
    {
        Carbon::setTestNow('2021-01-01 12:00:00');

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testMtCode',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null,
        ]);

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'gamecode' => 'testGameCode',
            'gamehall' => 'testGameHall',
            'roundid' => 'testRoundID',
            'amount' => 50.00,
            'mtcode' => 'testMtCode'
        ];

        $response = $this->post('/cq9/prov/transaction/game/debit', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '100',
                'message' => 'Something wrong.',
                'datetime' => '2021-01-01T00:00:00-04:00'
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_debit_insufficientFundException()
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
                    };
                }
            };
        });

        Carbon::setTestNow('2021-01-01 12:00:00');

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'gamecode' => 'testGameCode',
            'gamehall' => 'testGameHall',
            'roundid' => 'testRoundID',
            'amount' => 500.00,
            'mtcode' => 'testMtCode'
        ];

        $response = $this->post('/cq9/prov/transaction/game/debit', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '1005',
                'message' => 'Insufficient Balance.',
                'datetime' => '2021-01-01T00:00:00-04:00'
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_debit_invalidWalletResponse_expectedData()
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
                            return null;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return null;
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

        Carbon::setTestNow('2021-01-01 12:00:00');

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'gamecode' => 'testGameCode',
            'gamehall' => 'testGameHall',
            'roundid' => 'testRoundID',
            'amount' => 50.00,
            'mtcode' => 'testMtCode'
        ];

        $response = $this->post('/cq9/prov/transaction/game/debit', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '1100',
                'message' => 'Server error.',
                'datetime' => '2021-01-01T00:00:00-04:00'
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    /**
     * @dataProvider debitParams
     */
    public function test_debit_incompleteRequestParameters_expectedData($params)
    {
        Carbon::setTestNow('2021-01-01 12:00:00');

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'gamecode' => 'testGameCode',
            'gamehall' => 'testGameHall',
            'roundid' => 'testRoundID',
            'amount' => 50.00,
            'mtcode' => 'testMtCode'
        ];

        unset($request[$params]);

        $response = $this->post('/cq9/prov/transaction/game/debit', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '1003',
                'message' => 'Parameter error.',
                'datetime' => '2021-01-01T00:00:00-04:00'
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public static function debitParams()
    {
        return [
            ['account'],
            ['eventTime'],
            ['gamecode'],
            ['gamehall'],
            ['roundid'],
            ['amount'],
            ['mtcode']
        ];
    }
}
