<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Hg5WithdrawTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_withdraw_validRequest_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
            public function Wager(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $transactionID,
                float $amount,
                Wallet\V1\ProvSys\Transfer\Report $report
            ): array {
                return [
                    'credit_after' => 900.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                'balance' => 900.00,
                'currency' => 'IDR',
                'gameRound' => 'testGameRound'
            ],
            'status' => [
                'code' => 0,
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hg5.reports', [
            'ext_id' => 'wager-testGameRound',
            'round_id' => 'testGameRound',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_valid' => 100.00,
            'bet_amount' => 100.00,
            'bet_winlose' => 0,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    #[DataProvider('withdrawParams')]
    public function test_withdraw_invalidRequest_expectedData($parameter)
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        unset($request[$parameter]);

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 5,
                'message' => 'Bad parameters.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public static function withdrawParams(): array
    {
        return [
            ['playerId'],
            ['agentId'],
            ['amount'],
            ['currency'],
            ['gameCode'],
            ['gameRound'],
            ['eventTime']
        ];
    }

    public function test_withdraw_playerNotFoundException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'invalidPlayID',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 2,
                'message' => 'Player not found.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_withdraw_invalidTokenException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw',
            data: $request,
            headers: ['Authorization' => 'invalidToken']
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 3,
                'message' => 'Token Invalid',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_withdraw_invalidAgentIDException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 864153483,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 31,
                'message' => "Currency does not match Agent's currency.",
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_withdraw_transactionAlreadyExist_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wager-testGameRound1',
            'round_id' => 'testGameRound1',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_valid' => 100.00,
            'bet_amount' => 100.00,
            'bet_winlose' => 0,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 103,
                'message' => 'Transaction service error',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_withdraw_balanceProviderWalletError_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 41153531
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'amount' => 1000,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 105,
                'message' => 'Wallet service error.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_withdraw_insufficientFundException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 100.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'amount' => 1000,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 1,
                'message' => 'Insufficient balance.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_withdraw_wagerProviderWalletErrorException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
            public function Wager(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $transactionID,
                float $amount,
                Wallet\V1\ProvSys\Transfer\Report $report
            ): array {
                return [
                    'status_code' => 2453153
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 105,
                'message' => 'Wallet service error.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hg5.reports', [
            'ext_id' => 'wager-testGameRound',
            'round_id' => 'testGameRound',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_valid' => 100.00,
            'bet_amount' => 100.00,
            'bet_winlose' => 0,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }
}
