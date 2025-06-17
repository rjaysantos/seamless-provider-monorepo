<?php

use Tests\TestCase;
use App\Models\OrsReport;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class OrsDebitTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ors.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ors.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    #[DataProvider('gameCodesAndSignature')]
    public function test_debit_validRequest_expectedData($gameCode, $signature)
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function Wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('ors.players')->insert([
            'play_id' => '8dxw86xw6u027',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "player_id": "8dxw86xw6u027",
            "timestamp": 1715071526,
            "total_amount": 250,
            "transaction_type": "debit",
            "game_id": ' . $gameCode . ',
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "testTransactionID1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "testTransactionID2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "' . $signature . '"
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
            'player_id' => '8dxw86xw6u027',
            'total_amount' => 250,
            'updated_balance' => 0,
            'billing_at' => 1715071526,
            'records' => [
                [
                    'transaction_id' => 'testTransactionID1',
                    'secondary_info' => [],
                    'amount' => 150,
                    'other_info' => [],
                    'remark' => [],
                    'bet_place' => 'BASEGAME'
                ],
                [
                    'transaction_id' => 'testTransactionID2',
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
            'ext_id' => 'wager-testTransactionID1',
            'round_id' => 'testTransactionID1',
            'username' => 'testUsername',
            'play_id' => '8dxw86xw6u027',
            'web_id' => 27,
            'currency' => 'IDR',
            'game_code' => "{$gameCode}",
            'bet_amount' => 150.00,
            'bet_valid' => 150.00,
            'bet_winlose' => 0,
            'created_at' => '2024-05-07 16:45:26',
            'updated_at' => '2024-05-07 16:45:26',
        ]);

        $this->assertDatabaseHas('ors.reports', [
            'ext_id' => 'wager-testTransactionID2',
            'round_id' => 'testTransactionID2',
            'username' => 'testUsername',
            'play_id' => '8dxw86xw6u027',
            'web_id' => 27,
            'currency' => 'IDR',
            'game_code' => "{$gameCode}",
            'bet_amount' => 100.00,
            'bet_valid' => 100.00,
            'bet_winlose' => 0,
            'created_at' => '2024-05-07 16:45:26',
            'updated_at' => '2024-05-07 16:45:26',
        ]);
    }

    public static function gameCodesAndSignature()
    {
        return [
            [131, '525848e3f3058672b412a0f98333818c'],
            [123, '6d82066dc968c7c9488a6132b4c5b128']
        ];
    }

    public function test_debit_invalidSignature_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => '8dxw86xw6u027',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "player_id": "8dxw86xw6u027",
            "timestamp": 1715071526,
            "total_amount": 250,
            "transaction_type": "debit",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "testTransactionID1",
                    "secondary_info": {},
                    "amount": 50,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "testTransactionID2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "invalidSignature"
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

    public function test_debit_invalidPublicKeyHeader_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => '8dxw86xw6u027',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "player_id": "8dxw86xw6u027",
            "timestamp": 1715071526,
            "total_amount": 250,
            "transaction_type": "debit",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "testTransactionID1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "testTransactionID2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "6d82066dc968c7c9488a6132b4c5b128"
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

    public function test_debit_playerNotFound_expectedData()
    {
        $request = '{
            "player_id": "8dxw86xw6u027",
            "timestamp": 1715071526,
            "total_amount": 250,
            "transaction_type": "debit",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "testTransactionID1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "testTransactionID2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "6d82066dc968c7c9488a6132b4c5b128"
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

    public function test_debit_insufficientFunds_expectedData()
    {
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

        DB::table('ors.players')->insert([
            'play_id' => '8dxw86xw6u027',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "player_id": "8dxw86xw6u027",
            "timestamp": 1715071526,
            "total_amount": 250,
            "transaction_type": "debit",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "testTransactionID1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "testTransactionID2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "6d82066dc968c7c9488a6132b4c5b128"
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
            'rs_code' => 'S-103',
            'rs_message' => 'insufficient balance',
        ]);

        $response->assertStatus(200);
    }

    public function test_debit_transactionAlreadyExist_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => '8dxw86xw6u027',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'ext_id' => 'wager-testTransactionID2',
            'round_id' => 'testTransactionID2',
            'username' => 'testUsername',
            'play_id' => '8dxw86xw6u027',
            'web_id' => 27,
            'currency' => 'IDR',
            'game_code' => "123",
            'bet_amount' => 150.00,
            'bet_valid' => 150.00,
            'bet_winlose' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
        ]);

        $request = '{
            "player_id": "8dxw86xw6u027",
            "timestamp": 1715071526,
            "total_amount": 250,
            "transaction_type": "debit",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "testTransactionID1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "testTransactionID2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "6d82066dc968c7c9488a6132b4c5b128"
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
            'rs_code' => 'S-101',
            'rs_message' => 'transaction is duplicated',
        ]);

        $response->assertStatus(200);
    }

    public function test_debit_invalidWalletResponse_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }

            public function Wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('ors.players')->insert([
            'play_id' => '8dxw86xw6u027',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "player_id": "8dxw86xw6u027",
            "timestamp": 1715071526,
            "total_amount": 250,
            "transaction_type": "debit",
            "game_id": 123,
            "round_id": "182xk5xvw5az7j",
            "currency": "IDR",
            "called_at": 1715071526,
            "records": [
                {
                    "transaction_id": "testTransactionID1",
                    "secondary_info": {},
                    "amount": 150,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                },
                {
                    "transaction_id": "testTransactionID2",
                    "secondary_info": {},
                    "amount": 100,
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME"
                }
            ],
            "signature": "6d82066dc968c7c9488a6132b4c5b128"
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

        $this->assertDatabaseMissing('ors.reports', [
            'ext_id' => 'wager-testTransactionID1',
            'round_id' => 'testTransactionID1',
            'username' => 'testUsername',
            'play_id' => '8dxw86xw6u027',
            'web_id' => 27,
            'currency' => 'IDR',
            'game_code' => "123",
            'bet_amount' => 150.00,
            'bet_valid' => 150.00,
            'bet_winlose' => 0,
            'created_at' => '2024-05-07 16:45:26',
            'updated_at' => '2024-05-07 16:45:26',
        ]);

        $this->assertDatabaseMissing('ors.reports', [
            'ext_id' => 'wager-testTransactionID2',
            'round_id' => 'testTransactionID2',
            'username' => 'testUsername',
            'play_id' => '8dxw86xw6u027',
            'web_id' => 27,
            'currency' => 'IDR',
            'game_code' => "123",
            'bet_amount' => 100.00,
            'bet_valid' => 100.00,
            'bet_winlose' => 0,
            'created_at' => '2024-05-07 16:45:26',
            'updated_at' => '2024-05-07 16:45:26',
        ]);
    }

    #[DataProvider('debitParams')]
    public function test_debit_invalidRequest_expectedData($param)
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

    public static function debitParams()
    {
        return [
            [
                '{
                    "timestamp": 1715071526,
                    "total_amount": 250,
                    "transaction_type": "debit",
                    "game_id": 123,
                    "round_id": "182xk5xvw5az7j",
                    "currency": "IDR",
                    "called_at": 1715071526,
                    "records": [
                        {
                            "transaction_id": "testTransactionID1",
                            "secondary_info": {},
                            "amount": 150,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "testTransactionID2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "8c7272064010dc85ecbffb8852525f47"
                }'
            ],
            [
                '{
                    "player_id": "8dxw86xw6u027",
                    "timestamp": 1715071526,
                    "transaction_type": "debit",
                    "game_id": 123,
                    "round_id": "182xk5xvw5az7j",
                    "currency": "IDR",
                    "called_at": 1715071526,
                    "records": [
                        {
                            "transaction_id": "testTransactionID1",
                            "secondary_info": {},
                            "amount": 150,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "testTransactionID2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "88beac8a871fb39cc264378e257e32fb"
                }'
            ],
            [
                '{
                    "player_id": "8dxw86xw6u027",
                    "timestamp": 1715071526,
                    "total_amount": 250,
                    "game_id": 123,
                    "round_id": "182xk5xvw5az7j",
                    "currency": "IDR",
                    "called_at": 1715071526,
                    "records": [
                        {
                            "transaction_id": "testTransactionID1",
                            "secondary_info": {},
                            "amount": 150,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "testTransactionID2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "bfe0b5d965d8722ac160350cc8b7b6c1"
                }'
            ],
            [
                '{
                    "player_id": "8dxw86xw6u027",
                    "timestamp": 1715071526,
                    "total_amount": 250,
                    "transaction_type": "debit",
                    "round_id": "182xk5xvw5az7j",
                    "currency": "IDR",
                    "called_at": 1715071526,
                    "records": [
                        {
                            "transaction_id": "testTransactionID1",
                            "secondary_info": {},
                            "amount": 150,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "testTransactionID2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "f92ddbe43741570397b29aada7c1d98d"
                }'
            ],
            [
                '{
                    "player_id": "8dxw86xw6u027",
                    "timestamp": 1715071526,
                    "total_amount": 250,
                    "transaction_type": "debit",
                    "game_id": 123,
                    "currency": "IDR",
                    "called_at": 1715071526,
                    "records": [
                        {
                            "transaction_id": "testTransactionID1",
                            "secondary_info": {},
                            "amount": 150,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "testTransactionID2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "4c5add7b50ff7e6fd8c40161e0c6f635"
                }'
            ],
            [
                '{
                    "player_id": "8dxw86xw6u027",
                    "timestamp": 1715071526,
                    "total_amount": 250,
                    "transaction_type": "debit",
                    "game_id": 123,
                    "round_id": "182xk5xvw5az7j",
                    "currency": "IDR",
                    "records": [
                        {
                            "transaction_id": "testTransactionID1",
                            "secondary_info": {},
                            "amount": 150,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        },
                        {
                            "transaction_id": "testTransactionID2",
                            "secondary_info": {},
                            "amount": 100,
                            "other_info": {},
                            "remark": {},
                            "bet_place": "BASEGAME"
                        }
                    ],
                    "signature": "cb6f0f74162534f11e81c05f302dea89"
                }'
            ],
            [
                '{
                    "player_id": "8dxw86xw6u027",
                    "timestamp": 1715071526,
                    "total_amount": 250,
                    "transaction_type": "debit",
                    "game_id": 123,
                    "round_id": "182xk5xvw5az7j",
                    "currency": "IDR",
                    "called_at": 1715071526,
                    "signature": "17975ac9b9591965824aeaa30f158cbd"
                }'
            ],
        ];
    }
}
