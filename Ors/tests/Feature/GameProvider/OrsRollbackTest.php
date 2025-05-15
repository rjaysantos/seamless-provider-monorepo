<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;

class OrsRollbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ors.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ors.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_rollback_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Cancel(
                IWalletCredentials $credentials,
                string $transactionID,
                float $amount,
                string $transactionIDToCancel
            ): array {
                return [
                    'credit_after' => 250,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('ors.players')->insert([
            'play_id' => 'test_PlayerID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_1',
            'bet_amount' => 150.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_2',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = '{
            "player_id": "test_PlayerID",
            "timestamp": 1715071526,
            "total_amount": 0,
            "transaction_type": "rollback",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "test_transacID_1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "test_transacID_2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "3bd3363fb0624fcc0838f79a3279f5c5"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/bulk/debit',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_id' => 'test_PlayerID',
            'total_amount' => 0,
            'updated_balance' => 250.00,
            'billing_at' => 1715071526,
            'records' => [
                [
                    'transaction_id' => 'test_transacID_1',
                    'secondary_info' => [],
                    'amount' => 150,
                    'other_info' => [],
                    'remark' => [],
                    'bet_place' => 'BASEGAME'
                ],
                [
                    'transaction_id' => 'test_transacID_2',
                    'secondary_info' => [],
                    'amount' => 100,
                    'other_info' => [],
                    'remark' => [],
                    'bet_place' => 'BASEGAME'
                ]
            ],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('ors.reports', [
            'trx_id' => 'test_transacID_1',
            'bet_amount' => 150.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2024-05-07 16:45:26'
        ]);

        $this->assertDatabaseHas('ors.reports', [
            'trx_id' => 'test_transacID_2',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2024-05-07 16:45:26'
        ]);
    }

    public function test_rollback_invalidPublicKeyHeader_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'test_PlayerID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_1',
            'bet_amount' => 150.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_2',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = '{
            "player_id": "test_PlayerID",
            "timestamp": 1715071526,
            "total_amount": 0,
            "transaction_type": "rollback",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "test_transacID_1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "test_transacID_2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "3bd3363fb0624fcc0838f79a3279f5c5"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/bulk/debit',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'Invalid Key',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'E-102',
            'rs_message' => 'invalid public key in header',
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_invalidSignature_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'test_PlayerID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_1',
            'bet_amount' => 150.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_2',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = '{
            "player_id": "test_PlayerID",
            "timestamp": 1715071526,
            "total_amount": 0,
            "transaction_type": "rollback",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "test_transacID_1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "test_transacID_2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "Invalid Signature"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/bulk/debit',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'E-103',
            'rs_message' => 'invalid signature',
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_playerNotFound_expectedData()
    {
        $request = '{
            "player_id": "test_PlayerID",
            "timestamp": 1715071526,
            "total_amount": 0,
            "transaction_type": "rollback",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "test_transacID_1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "test_transacID_2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "3bd3363fb0624fcc0838f79a3279f5c5"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/bulk/debit',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'S-104',
            'rs_message' => 'player not available',
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_transactionNotFound_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'test_PlayerID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_1',
            'bet_amount' => 150.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_3',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = '{
            "player_id": "test_PlayerID",
            "timestamp": 1715071526,
            "total_amount": 0,
            "transaction_type": "rollback",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "test_transacID_1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "test_transacID_2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "3bd3363fb0624fcc0838f79a3279f5c5"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/bulk/debit',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'S-119',
            'rs_message' => 'transaction does not existed',
        ]);

        $response->assertStatus(200);
    }

    public function test_rollback_invalidWalletResponse_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Cancel(
                IWalletCredentials $credentials,
                string $transactionID,
                float $amount,
                string $transactionIDToCancel
            ): array {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('ors.players')->insert([
            'play_id' => 'test_PlayerID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_1',
            'bet_amount' => 150.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        DB::table('ors.reports')->insert([
            'trx_id' => 'test_transacID_2',
            'bet_amount' => 100.00,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => null
        ]);

        $request = '{
            "player_id": "test_PlayerID",
            "timestamp": 1715071526,
            "total_amount": 0,
            "transaction_type": "rollback",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "test_transacID_1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "test_transacID_2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "3bd3363fb0624fcc0838f79a3279f5c5"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/bulk/debit',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_message' => 'internal error on the operator',
            'rs_code' => 'S-113'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider rollbackParams
     */
    public function test_rollback_incompleteParameters_expectedData($param)
    {
        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/bulk/debit',
            json_decode($param, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $param
        );

        $response->assertJson([
            'rs_message' => 'invalid parameter',
            'rs_code' => 'E-104',
        ]);

        $response->assertStatus(200);
    }

    public static function rollbackParams()
    {
        return [
            [
                '{
                    "transaction_type": "rollback",
                    "called_at": 1715071526,
                    "records": [
                        {
                            "transaction_id": "test_transacID_1",
                            "secondary_info": {},
                            "amount": 150,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "test_transacID_2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "invalid_signature"
                }'
            ],
            [
                '{
                    "player_id": "test_PlayerID",
                    "transaction_type": "rollback",
                    "records": [
                        {
                            "transaction_id": "test_transacID_1",
                            "secondary_info": {},
                            "amount": 150,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "test_transacID_2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "invalid_signature"
                }'
            ],
            [
                '{
                    "player_id": "test_PlayerID",
                    "transaction_type": "rollback",
                    "called_at": 1715071526,
                    "signature": "invalid_signature"
                }'
            ],
            [
                '{
                    "player_id": "test_PlayerID",
                    "transaction_type": "rollback",
                    "called_at": 1715071526,
                    "records": [
                        {
                            "transaction_id": "test_transacID_1",
                            "secondary_info": {},
                            "amount": 150,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "test_transacID_2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ]
                }'
            ],

        ];
    }
}
