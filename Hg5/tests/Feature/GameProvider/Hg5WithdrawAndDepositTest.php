<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Hg5WithdrawAndDepositTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_withdrawAndDeposit_validRequestSlot_expectedData()
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
            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1200.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            '/GrandPriest/gameList' => Http::response(json_encode([
                'data' => [
                    [
                        'gamecode' => 'testGameCode',
                        'gametype' => 'slot'
                    ]
                ],
                'status' => [
                    'code' => '0'
                ]
            ]))
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                'balance' => 1200,
                'currency' => 'IDR',
                'gameRound' => 'hg5-testGameRound'
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hg5.reports', [
            'ext_id' => 'wagerpayout-hg5-testGameRound',
            'round_id' => 'hg5-testGameRound',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    public function test_withdrawAndDeposit_validRequestArcade_expectedData()
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
            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1200.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            '/GrandPriest/gameList' => Http::response(json_encode([
                'data' => [
                    [
                        'gamecode' => 'testGameCode',
                        'gametype' => 'arcade'
                    ]
                ],
                'status' => [
                    'code' => '0'
                ]
            ]))
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                'balance' => 1200,
                'currency' => 'IDR',
                'gameRound' => 'hg5-testGameRound'
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hg5.reports', [
            'ext_id' => 'wagerpayout-hg5-testGameRound',
            'round_id' => 'hg5-testGameRound',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    public function test_withdrawAndDeposit_validRequestFreeGame_expectedData()
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
            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'credit_after' => 1200.00,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            '/GrandPriest/gameList' => Http::response(json_encode([
                'data' => [
                    [
                        'gamecode' => 'testGameCode',
                        'gametype' => 'slot'
                    ]
                ],
                'status' => [
                    'code' => '0'
                ]
            ]))
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wagerpayout-hg5-testGameRound1',
            'round_id' => 'hg5-testGameRound1',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'withdrawAmount' => 0,
            'depositAmount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound2',
            'eventTime' => '2024-01-02T00:00:00-04:00',
            'extra' => [
                'slot' => [
                    'stage' => 'fg',
                    'mainBet' => '100',
                    'mainGameRound' => 'hg5-testGameRound1'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                'balance' => 1200,
                'currency' => 'IDR',
                'gameRound' => 'hg5-testGameRound2'
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hg5.reports', [
            'ext_id' => 'wagerpayout-hg5-testGameRound2',
            'round_id' => 'hg5-testGameRound2',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_amount' => 0.00,
            'bet_winlose' => 200.00,
            'created_at' => '2024-01-02 12:00:00',
            'updated_at' => '2024-01-02 12:00:00'
        ]);
    }

    #[DataProvider('wagerAndPayoutParams')]
    public function test_withdrawAndDeposit_invalidRequest_expectedData($parameter)
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00',
            'extra' => [
                'slot' => [
                    'stage' => 'fg',
                    'mainBet' => '100',
                    'mainGameRound' => 'hg5-testGameRound1'
                ]
            ]
        ];

        unset($request[$parameter]);

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
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

    public static function wagerAndPayoutParams()
    {
        return [
            ['playerId'],
            ['agentId'],
            ['withdrawAmount'],
            ['depositAmount'],
            ['currency'],
            ['gameCode'],
            ['gameRound'],
            ['eventTime']
        ];
    }

    public function test_withdrawAndDeposit_playerNotFoundException_expectedData()
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
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
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

    public function test_withdrawAndDeposit_invalidTokenException_expectedData()
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
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
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

    public function test_withdrawAndDeposit_invalidAgentIDException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 5621681381684135,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
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

    public function test_withdrawAndDeposit_freeGameTransactionNotFoundException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wagerpayout-hg5-testGameRound1',
            'round_id' => 'hg5-testGameRound1',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'withdrawAmount' => 0,
            'depositAmount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound2',
            'eventTime' => '2024-01-02T00:00:00-04:00',
            'extra' => [
                'slot' => [
                    'stage' => 'fg',
                    'mainBet' => '100',
                    'mainGameRound' => 'invalidGameRound'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '36',
                'message' => 'GameRound not existed.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_withdrawAndDeposit_transactionAlreadyExistException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'ext_id' => 'wagerpayout-hg5-testGameRound',
            'round_id' => 'hg5-testGameRound',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
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

    public function test_withdrawAndDeposit_balanceProviderWalletErrorException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 384846153438
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
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
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

    public function test_withdrawAndDeposit_insufficientFundException_expectedData()
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
            'withdrawAmount' => 1000,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
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

    public function test_withdrawAndDeposit_gameNotFound_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        Http::fake([
            '/GrandPriest/gameList' => Http::response(json_encode([
                'data' => [
                    [
                        'gamecode' => 'testGameCode',
                        'gametype' => 'slot'
                    ]
                ],
                'status' => [
                    'code' => '0'
                ]
            ]))
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'invalidCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 13,
                'message' => 'Game is not found.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hg5.reports', [
            'ext_id' => 'wagerpayout-hg5-testGameRound',
            'round_id' => 'hg5-testGameRound',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'invalidCode',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    public function test_withdrawAndDeposit_thirdPartyError_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        Http::fake([
            '/GrandPriest/gameList' => Http::response(json_encode([
                'data' => null,
                'status' => [
                    'code' => '534345'
                ]
            ]))
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 100,
                'message' => 'Something Wrong.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hg5.reports', [
            'ext_id' => 'wagerpayout-hg5-testGameRound',
            'round_id' => 'hg5-testGameRound',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    public function test_withdrawAndDeposit_wagerAndPayoutProviderWalletErrorException_expectedData()
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
            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 168168168506
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        Http::fake([
            '/GrandPriest/gameList' => Http::response(json_encode([
                'data' => [
                    [
                        'gamecode' => 'testGameCode',
                        'gametype' => 'slot'
                    ]
                ],
                'status' => [
                    'code' => '0'
                ]
            ]))
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayIDu001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'playerId' => 'testPlayIDu001',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'hg5-testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/withdraw_deposit',
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
            'ext_id' => 'wagerpayout-hg5-testGameRound',
            'round_id' => 'hg5-testGameRound',
            'username' => 'testUsername',
            'play_id' => 'testPlayIDu001',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 'testGameCode',
            'bet_amount' => 100.00,
            'bet_winlose' => 200.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }
}
