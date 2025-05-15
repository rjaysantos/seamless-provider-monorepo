<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Cq9Player;
use App\Models\Cq9Report;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Cq9PayoffTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE cq9.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE cq9.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_payoff_validRequest_expectedData()
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
                            return [
                                'credit_after' => 200.0
                            ];
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

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'amount' => 100.00,
            'mtcode' => 'testTransactionID',
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('cq9/prov/transaction/user/payoff', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => [
                'balance' => 200.0,
                'currency' => 'IDR'
            ],
            'status' => [
                'code' => 0,
                'message' => 'Success',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cq9.reports', [
            'trx_id' => 'testTransactionID',
            'bet_amount' => 0,
            'win_amount' => 100.00,
            'created_at' => '2021-01-01 12:00:00',
            'updated_at' => '2021-01-01 12:00:00'
        ]);
    }

    public function test_payoff_invalidEventTime_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID'
        ]);

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2020-01-01',
            'amount' => 100.00,
            'mtcode' => 'testTransactionID',
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('cq9/prov/transaction/user/payoff', $request, [
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

    public function test_payoff_invalidWToken_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'amount' => 100.00,
            'mtcode' => 'testTransactionID',
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('cq9/prov/transaction/user/payoff', $request, [
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

    public function test_payoff_playerNotFound_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'nonexistentPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'amount' => 100.00,
            'mtcode' => 'testTransactionID',
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('cq9/prov/transaction/user/payoff', $request, [
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

    public function test_payoff_transactionAlreadyExist_expectedData()
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
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'amount' => 100.00,
            'mtcode' => 'testTransactionID',
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('cq9/prov/transaction/user/payoff', $request, [
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

    public function test_payoff_emptyWalletResponse_expectedData()
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

        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'amount' => 100.00,
            'mtcode' => 'testTransactionID',
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('cq9/prov/transaction/user/payoff', $request, [
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
     * @dataProvider payoffParams
     */
    public function test_payoff_incompleteParameters_expectedData($param)
    {
        $request = [
            'account' => 'testPlayID',
            'eventTime' => '2021-01-01T00:00:00.00-04:00',
            'amount' => 100.00,
            'mtcode' => 'testTransactionID',
        ];

        unset($request[$param]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('cq9/prov/transaction/user/payoff', $request, [
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

    public static function payoffParams()
    {
        return [
            ['account'],
            ['eventTime'],
            ['amount'],
            ['mtcode'],
        ];
    }
}
