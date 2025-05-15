<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class BesSettleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE bes.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE bes.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_settle_validData_successResponse()
    {
        DB::table('bes.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
            ]);

        Carbon::setTestNow('2024-01-01 00:00:00');

        $request = [
            'action' => 3,
            'uid' => 'test-player-1',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'spjpbet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ];

        $wallet = new class extends TestWallet {
            public function payout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $transactionID,
                float $amount,
                Report $report
            ): array {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'action' => 3,
            'status' => 1,
            'balance' => 1000.0,
            'currency' => 'IDR'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bes.reports', [
            'trx_id' => '12345-6789',
            'bet_amount' => 100.0,
            'win_amount' => 10,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);
    }

    #[DataProvider('settleParams')]
    public function test_settle_incompleteRequestParameters_invalidRequestResponse($param)
    {
        $request = [
            'action' => 2,
            'mode' => 0,
            'bet' => 100.0,
            'uid' => 'test-player-1',
            'gid' => '1',
            'roundId' => '12345',
            'transId' => '6789',
            'ts' => 1704038400
        ];

        unset($request[$param]);

        app()->bind(IWallet::class, TestWallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'status' => 1008
        ]);

        $response->assertStatus(200);
    }

    public static function settleParams()
    {
        return [
            ['action'],
            ['mode'],
            ['bet'],
            ['uid'],
            ['gid'],
            ['roundId'],
            ['transId'],
            ['ts'],
        ];
    }

    public function test_settle_playerIDNotFound_PlayerNotFoundResponse()
    {
        $request = [
            'action' => 3,
            'uid' => 'test-player-1',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'spjpbet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ];

        $wallet = new class extends TestWallet {
            public function payout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $transactionID,
                float $amount,
                Report $report
            ): array {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'status' => 1004
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_transactionAlreadyExist_TransactionAlreadyExistResponse()
    {
        DB::table('bes.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
            ]);

        DB::table('bes.reports')
            ->insert([
                'trx_id' => '12345-6789',
                'bet_amount' => 100.0,
                'win_amount' => 0,
                'updated_at' => Carbon::now()
            ]);

        Carbon::setTestNow('2024-01-01 00:00:00');

        $request = [
            'action' => 3,
            'uid' => 'test-player-1',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'spjpbet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ];

        $wallet = new class extends TestWallet {
            public function payout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $transactionID,
                float $amount,
                Report $report
            ): array {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'status' => 1031,
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_walletBalanceError_TransactionAlreadyExistResponse()
    {
        DB::table('bes.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
            ]);

        DB::table('bes.reports')
            ->insert([
                'trx_id' => '12345-6789',
                'bet_amount' => 100.0,
                'win_amount' => 0,
                'updated_at' => Carbon::now()
            ]);

        Carbon::setTestNow('2024-01-01 00:00:00');

        $request = [
            'action' => 3,
            'uid' => 'test-player-1',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'spjpbet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ];

        $wallet = new class extends TestWallet {
            public function balance(
                IWalletCredentials $credentials,
                string $playID
            ): array {
                return [
                    'status_code' => 'invalid error'
                ];
            }

            public function wagerAndPayout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $wagerTransactionID,
                float $wagerAmount,
                string $payoutTransactionID,
                float $payoutAmount,
                Report $report
            ): array {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'status' => 1031,
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_insufficientBalance_insufficientBalanceResponse()
    {
        DB::table('bes.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
            ]);

        Carbon::setTestNow('2024-01-01 00:00:00');

        $request = [
            'action' => 3,
            'uid' => 'test-player-1',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'spjpbet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ];

        $wallet = new class extends TestWallet {
            public function balance(
                IWalletCredentials $credentials,
                string $playID
            ): array {
                return [
                    'credit' => 0,
                    'status_code' => 2100
                ];
            }

            public function wagerAndPayout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $wagerTransactionID,
                float $wagerAmount,
                string $payoutTransactionID,
                float $payoutAmount,
                Report $report
            ): array {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'status' => 1009,
        ]);

        $response->assertStatus(200);
    }

    public function test_settle_walletWagerAndPayoutError_walletErrorResponse()
    {
        DB::table('bes.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
            ]);

        Carbon::setTestNow('2024-01-01 00:00:00');

        $request = [
            'action' => 3,
            'uid' => 'test-player-1',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'spjpbet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ];

        $wallet = new class extends TestWallet {
            public function wagerAndPayout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $wagerTransactionID,
                float $wagerAmount,
                string $payoutTransactionID,
                float $payoutAmount,
                Report $report
            ): array {
                return [
                    'status_code' => 'invalid error'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'status' => 1014,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('bes.reports', [
            'trx_id' => '12345-6789',
            'bet_amount' => 100.0,
            'win_amount' => 10,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);
    }
}
