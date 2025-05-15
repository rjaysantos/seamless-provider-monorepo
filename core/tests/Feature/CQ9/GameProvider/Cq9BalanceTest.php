<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Cq9Player;
use App\Contracts\IWallet;
use App\Contracts\IGrpcLib;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class Cq9BalanceTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE cq9.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE cq9.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_balance_validRequest_expectedData()
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
                    };
                }
            };
        });

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $playID = 'testPlayID';

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->get("/cq9/prov/transaction/balance/{$playID}", [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => [
                'balance' => 1000.0,
                'currency' => 'IDR'
            ],
            'status' => [
                'code' => '0',
                'message' => 'Success',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_invalidWToken_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $playID = 'testPlayID';

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->get("/cq9/prov/transaction/balance/{$playID}", [
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

    public function test_balance_playerNotFound_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
        ]);

        $playID = 'nonexistentPlayID';

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->get("/cq9/prov/transaction/balance/{$playID}", [
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

    public function test_balance_emptyWalletResponse_expectedData()
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

        $playID = 'testPlayID';

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->get("/cq9/prov/transaction/balance/{$playID}", [
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
}
