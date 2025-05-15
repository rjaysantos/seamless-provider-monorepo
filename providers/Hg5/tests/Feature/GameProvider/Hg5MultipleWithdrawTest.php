<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Hg5MultipleWithdrawTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_multipleWithdraw_validRequest_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 100.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 300.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_withdraw',
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
                    'balance' => 900.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 900.00,
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
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 300.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);
    }

    #[DataProvider('multipleWithdrawParams')]
    public function test_multipleWithdraw_invalidRequest_expectedData($parameter)
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
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        if ($parameter == 'datas')
            unset($request[$parameter]);

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_withdraw',
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

    public static function multipleWithdrawParams()
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

    public function test_multipleWithdraw_playerNotFound_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'invalidPlayerID',
                    'agentId' => 111,
                    'amount' => 100.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 300.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_withdraw',
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
                    'playerId' => 'invalidPlayerID',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 900.00,
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
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 300.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);
    }

    public function test_multipleWithdraw_invalidToken_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 100.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 300.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_withdraw',
            data: $request,
            headers: [
                'Authorization' => 'invalidToken'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => 3,
                    'message' => 'Token Invalid',
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => 3,
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

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound1',
            'bet_amount' => 100.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 300.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);
    }

    public function test_multipleWithdraw_invalidAgentID_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 3431350,
                    'amount' => 100.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 300.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => 31,
                    'message' => "Currency does not match Agent's currency.",
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 3431350,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 900.00,
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
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 300.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);
    }

    public function test_multipleWithdraw_transactionAlreadyExist_expectedData()
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

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 100.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 300.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_withdraw',
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
                    'balance' => 900.00,
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

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 300.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);
    }

    public function test_multipleWithdraw_balanceWalletError_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 34345354
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

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 100.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 300.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_withdraw',
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
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 300.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);
    }

    public function test_multipleWithdraw_insufficientFund_expectedData()
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
            'play_id' => 'testPlayID1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID2',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 10000.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 300.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_withdraw',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                [
                    'code' => '1',
                    'message' => 'Insufficient balance.',
                    'balance' => 0.00,
                    'currency' => 'IDR',
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'gameRound' => 'testGameRound1'
                ],
                [
                    'code' => '0',
                    'message' => '',
                    'balance' => 900.00,
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
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseHas('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 300.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);
    }

    public function test_multipleWithdraw_wagerProviderWalletError_expectedData()
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
                    'status_code' => 4586415384
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

        $request = [
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 100.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 300.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound2',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/multi_withdraw',
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
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);

        $this->assertDatabaseMissing('hg5.reports', [
            'trx_id' => 'testGameRound2',
            'bet_amount' => 300.00,
            'win_amount' => 0.00,
            'created_at' => '2024-01-01 12:00:00',
            'updated_at' => null
        ]);
    }
}