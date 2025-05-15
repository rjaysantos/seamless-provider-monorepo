<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Cq9Player;
use App\Models\Cq9Report;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class Cq9RefundTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE cq9.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE cq9.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_refund_validRequest_expectedData()
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
                            return [
                                'credit_after' => 900.0
                            ];
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
            'mt_code' => 'rel-bet-test123:cq9',
            'updated_at' => null
        ]);

        $request = [
            'account' => 'testPlayID',
            'mtcode' => 'rel-bet-test123:cq9'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/refund', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        Carbon::setTestNow('2020-01-01 12:00:00');

        $response->assertJson([
            'data' => [
                'balance' => 900.0,
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
            'win_amount' => 0,
            'updated_at' => '2020-01-01 12:00:00'
        ]);
    }

    public function test_refund_invalidWToken_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'testPlayID',
            'mtcode' => 'rel-bet-test123:cq9'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/refund', $request, [
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

    public function test_refund_playerNotFound_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $request = [
            'account' => 'nonexistentPlayID',
            'mtcode' => 'rel-bet-test123:cq9'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/refund', $request, [
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

    public function test_refund_transactionNotFound_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID',
            'mt_code' => 'rel-bet-test123:cq9',
            'updated_at' => null
        ]);

        $request = [
            'account' => 'testPlayID',
            'mtcode' => 'nonExistentMtCode'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/refund', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '1014',
                'message' => 'Transaction record not found.',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_refund_transactionAlreadySettled_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID',
            'mt_code' => 'rel-bet-test123:cq9',
            'updated_at' => '2020-01-01 12:00:00'
        ]);

        $request = [
            'account' => 'testPlayID',
            'mtcode' => 'rel-bet-test123:cq9'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/refund', $request, [
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

    public function test_refund_emptyWalletResponse_expectedData()
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
            'mt_code' => 'rel-bet-test123:cq9',
            'updated_at' => null
        ]);

        $request = [
            'account' => 'testPlayID',
            'mtcode' => 'rel-bet-test123:cq9'
        ];

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/refund', $request, [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => false,
            'status' => [
                'code' => '1100',
                'message' => 'Server error.',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider refundParams
     */
    public function test_refund_incompleteParam_expectedData($param)
    {
        $request = [
            'account' => 'testPlayID',
            'mtcode' => 'rel-bet-test123:cq9'
        ];

        unset($request[$param]);

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->post('/cq9/prov/transaction/game/bet', $request, [
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

    public static function refundParams()
    {
        return [
            ['account'],
            ['mtcode'],
        ];
    }
}
