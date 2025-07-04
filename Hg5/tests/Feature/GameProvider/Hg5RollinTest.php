<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Hg5RollinTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_rollin_validRequest_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function Payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1200.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wager-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            "updated_at" => "2025-01-01 00:00:00",
            "created_at" => "2025-01-01 00:00:00"
        ]);

        $request = [
            'playerId' => 'testPlayeru001',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => '1',
            'gameRound' => 'testTransactionID',
            'eventTime' => '2025-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/rollin',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                'balance' => 1200.00,
                'currency' => 'IDR'
            ],
            'status' => [
                'code' => 0,
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hg5.reports', [
            'ext_id' => 'payout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '1',
            'bet_valid' => 0,
            'bet_amount' => 0,
            'bet_winlose' => 100,
            "updated_at" => "2025-01-01 12:00:00",
            "created_at" => "2025-01-01 12:00:00"
        ]);
    }

    #[DataProvider('rollinParameter')]
    public function test_rollin_invalidRequest_expectedData($parameter)
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $request = [
            'playerId' => 'testPlayeru001',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => '1',
            'gameRound' => 'testTransactionID',
            'eventTime' => '2025-01-01T00:00:00-04:00'
        ];

        unset($request[$parameter]);

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/rollin',
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

    public static function rollinParameter()
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

    public function test_rollin_playerNotfound_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken'
        ]);

       $request = [
            'playerId' => 'invalidPlayer',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => '1',
            'gameRound' => 'testTransactionID',
            'eventTime' => '2025-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/rollin',
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

    public function test_rollin_invalidToken_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken'
        ]);

        $request = [
            'playerId' => 'testPlayeru001',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => '1',
            'gameRound' => 'testTransactionID',
            'eventTime' => '2025-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/rollin',
            data: $request,
            headers: [
                'Authorization' => 'invalidToken'
            ]
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

    public function test_rollin_invalidAgentID_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken'
        ]);

        $request = [
            'playerId' => 'testPlayeru001',
            'agentId' => 11231211,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => '1',
            'gameRound' => 'testTransactionID',
            'eventTime' => '2025-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/rollin',
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

    public function test_rollin_transactionNotFound_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wager-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            "updated_at" => "2025-01-01 00:00:00",
            "created_at" => "2025-01-01 00:00:00"
        ]);

        $request = [
            'playerId' => 'testPlayeru001',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => '1',
            'gameRound' => 'invalidGameRound',
            'eventTime' => '2025-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/rollin',
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

    public function test_rollin_transactionAlreadySettled_expectedData()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken'
        ]);

        DB::table('hg5.reports')->insert([
            [
                'ext_id' => 'wager-testTransactionID',
                'round_id' => 'testTransactionID',
                'username' => 'testUsername',
                'play_id' => 'testPlayeru001',
                'web_id' => 1,
                'currency' => 'IDR',
                'game_code' => 1,
                'bet_amount' => 100.0,
                'bet_winlose' => 0,
                "updated_at" => "2025-01-01 00:00:00",
                "created_at" => "2025-01-01 00:00:00"
            ],
            [
                'ext_id' => 'payout-testTransactionID',
                'round_id' => 'testTransactionID',
                'username' => 'testUsername',
                'play_id' => 'testPlayeru001',
                'web_id' => 1,
                'currency' => 'IDR',
                'game_code' => 1,
                'bet_amount' => 100.0,
                'bet_winlose' => 0,
                "updated_at" => "2025-01-01 12:00:00",
                "created_at" => "2025-01-01 12:00:00"
            ]
        ]);

        $request = [
            'playerId' => 'testPlayeru001',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => '1',
            'gameRound' => 'testTransactionID',
            'eventTime' => '2025-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/rollin',
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
                'datetime' => '2025-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_rollin_walletWagerAndPayoutFail()
    {
        Carbon::setTestNow('2025-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function Payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 4651353
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayeru001',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'testToken'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wager-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            "updated_at" => "2025-01-01 12:00:00",
            "created_at" => "2025-01-01 12:00:00"
        ]);

        $request = [
            'playerId' => 'testPlayeru001',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => '1',
            'gameRound' => 'testTransactionID',
            'eventTime' => '2025-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/rollin',
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
                'datetime' => '2025-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hg5.reports', [
            'ext_id' => 'payout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 1,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            "updated_at" => "2025-01-01 12:00:00",
            "created_at" => "2025-01-01 12:00:00"
        ]);
    }
}