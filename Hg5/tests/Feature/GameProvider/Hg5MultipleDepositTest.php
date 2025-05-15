<?php

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;

class Hg5MultipleDepositTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_multipleDeposit_validRequest_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 1200.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 1200.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound2'
                ],
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 200.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 500.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    #[DataProvider('multipleDepositParams')]
    public function test_multipleDeposit_invalidRequest_expectedData($parameter)
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $datasArray = [
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 100.00,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ];

        if ($parameter != 'datas')
            unset($datasArray[$parameter]);

        $request = [
            'datas' => [
                (object) $datasArray,
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 300.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        if ($parameter == 'datas')
            unset($request[$parameter]);

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '5',
                'message' => 'Bad parameters.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public static function multipleDepositParams()
    {
        return [
            ['datas'],
            ['playerId'],
            ['agentId'],
            ['amount'],
            ['currency'],
            ['gameCode'],
            ['gameRound'],
            ['eventTime']
        ];
    }

    public function test_multipleDeposit_playerNotFound_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'invalidPlayer',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => '2',
                    'message' => 'Player not found.',
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'invalidPlayer',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 1200.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound2'
                ],
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 200.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 500.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    public function test_multipleDeposit_invalidToken_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_deposit',
            data: $request,
            headers: [
                'Authorization' => 'invalidToken'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => '3',
                    'message' => 'Token Invalid',
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '3',
                    'message' => 'Token Invalid',
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound2'
                ],
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_multipleDeposit_invalidAgentID_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 31535351,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => '31',
                    'message' => "Currency does not match Agent's currency.",
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 31535351,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 1200.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound2'
                ],
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 200.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 500.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    public function test_multipleDeposit_transactionNotFound_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'invalidGameRound',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => 36,
                    'message' => 'GameRound not existed.',
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'gameRound' => 'invalidGameRound'
                ],
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 1200.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound2'
                ],
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 500.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    public function test_multipleDeposit_transactionAlreadySettled_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 200.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => 103,
                    'message' => 'Transaction service error',
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 1200.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound2'
                ],
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 500.00,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }

    public function test_multipleDeposit_payoutWalletError_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function Payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Wallet\V1\ProvSys\Transfer\Report $report): array
            {
                return [
                    'status_code' => 3153351543
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('hg5.reports')->insert([
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_deposit',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => '105',
                    'message' => 'Wallet service error.',
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '105',
                    'message' => 'Wallet service error.',
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound2'
                ],
            ],
            'status' => [
                'code' => '0',
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 200.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 200.00,
            'win_amount' => 500.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => '2024-01-01 12:00:00'
        ]);
    }
}