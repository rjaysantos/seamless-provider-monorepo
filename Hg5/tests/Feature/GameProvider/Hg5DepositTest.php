<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Hg5DepositTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_deposit_validRequestFirstPayout_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function Payout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $transactionID,
                float $amount,
                Wallet\V1\ProvSys\Transfer\Report $report
            ): array {
                return [
                    'credit_after' => 1300.00,
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
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                'balance' => 1300.00,
                'currency' => 'IDR',
                'gameRound' => 'testGameRound1'
            ],
            'status' => [
                'code' => 0,
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hg5.reports', [
            'ext_id' => 'payout-testGameRound1',
            'round_id' => 'testGameRound1',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_valid' => 0,
            'bet_amount' => 0,
            'bet_winlose' => 200.0,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    #[DataProvider('depositParams')]
    public function test_deposit_invalidRequest_expectedData($parameter)
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $request = [
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        unset($request[$parameter]);

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/deposit',
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

    public static function depositParams()
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

    public function test_deposit_playerNotFound_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'invalidPlayerID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/deposit',
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

    public function test_deposit_invalidTokenException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/deposit',
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

    public function test_deposit_invalidAgentIDException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayID',
            'agentId' => 561135611335,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/deposit',
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

    public function test_deposit_transactionAlreadySettled_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wager-testGameRound1',
            'round_id' => 'testGameRound1',
            'username' => 'testUsername',
            'play_id' => 'testPlayID',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_valid' => 100.00,
            'bet_amount' => 100.00,
            'bet_winlose' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'payout-testGameRound1',
            'round_id' => 'testGameRound1',
            'username' => 'testUsername',
            'play_id' => 'testPlayID',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_valid' => 0.00,
            'bet_amount' => 0.00,
            'bet_winlose' => 200.0,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        $request = [
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/deposit',
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

    public function test_deposit_transactionNotFoundException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wager-testGameRound1',
            'round_id' => 'testGameRound1',
            'username' => 'testUsername',
            'play_id' => 'testPlayID',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_valid' => 100.00,
            'bet_amount' => 100.00,
            'bet_winlose' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);

        $request = [
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'invalidGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 36,
                'message' => 'GameRound not existed.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_deposit_payoutProviderWalletErrorException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function Payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 535344353
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wager-testGameRound1',
            'round_id' => 'testGameRound1',
            'username' => 'testUsername',
            'play_id' => 'testPlayID',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_valid' => 100.00,
            'bet_amount' => 100.00,
            'bet_winlose' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);

        $request = [
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'mtCode' => 'testMtCode2',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/deposit',
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
            'ext_id' => 'payout-testGameRound1',
            'round_id' => 'testGameRound1',
            'username' => 'testUsername',
            'play_id' => 'testPlayID',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_valid' => 0.00,
            'bet_amount' => 0.00,
            'bet_winlose' => 200.0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00'
        ]);
    }
}
