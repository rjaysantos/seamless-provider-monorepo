<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class OrsCreditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ors.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ors.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    #[DataProvider('gameCodesAndSignature')]
    public function test_credit_validData_expectedData($gameCode, $signature)
    {
        $wallet = new class extends TestWallet {
            public function payout(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 900.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('ors.players')->insert([
            'play_id' => 'testPlayeru1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'ext_id' => 'wager-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => $gameCode,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00',
        ]);

        $request = '{
            "transaction_id": "testTransactionID",
            "secondary_info": {},
            "amount": 30,
            "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
            "other_info": {},
            "called_at": 1735689600,
            "remark": {},
            "bet_place": "BASEGAME",
            "transaction_type": "credit",
            "round_id": "testTransactionID",
            "effective_amount": 250,
            "currency": "IDR",
            "winlose_amount": -220,
            "game_code": "pocketjungle",
            "timestamp": 1735689600,
            "player_id": "testPlayeru1",
            "game_id": ' . $gameCode . ',
            "signature": "' . $signature . '"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/credit',
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
            'player_id' => 'testPlayeru1',
            'amount' => 30,
            'transaction_id' => 'testTransactionID',
            'updated_balance' => 900.0,
            'billing_at' => 1735689600,
        ]);

        $this->assertDatabaseHas('ors.reports', [
            'ext_id' => 'payout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => (string)$gameCode,
            'bet_valid' => 0,
            'bet_amount' => 0,
            'bet_winlose' => -70,
            'created_at' => '2025-01-01 08:00:00.00',
            'updated_at' => '2025-01-01 08:00:00.00',
        ]);
    }

    public static function gameCodesAndSignature()
    {
        return [
            [131, '81de4e455c88d2f6bb039ee27b6cc42c'],
            [123, '3f7c07b8de5778eb27a4e305587cbbfe']
        ];
    }

    public function test_credit_validData1stCreditTimeout_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function Balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 200.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('ors.players')->insert([
            'play_id' => 'testPlayerIDu1',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ors.reports')->insert([
            'ext_id' => 'wager-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayerIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '131',
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00',
        ]);

        DB::table('ors.reports')->insert([
            'ext_id' => 'payout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayerIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '131',
            'bet_amount' => 0,
            'bet_winlose' => 0,
            'created_at' => '2025-01-01 08:00:00',
            'updated_at' => '2025-01-01 08:00:00',
        ]);

        $request = '{
            "transaction_id": "testTransactionID",
            "secondary_info": {},
            "amount": 300,
            "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
            "other_info": {},
            "called_at": 1735689600,
            "remark": {},
            "bet_place": "BASEGAME",
            "transaction_type": "credit",
            "round_id": "testTransactionID",
            "effective_amount": 250,
            "currency": "IDR",
            "winlose_amount": -220,
            "game_code": "pocketjungle",
            "timestamp": 1735689600,
            "player_id": "testPlayerIDu1",
            "game_id": 123,
            "signature": "3beb04646eb04e49578a03361345db6e"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/credit',
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
            'player_id' => 'testPlayerIDu1',
            'amount' => 300,
            'transaction_id' => 'testTransactionID',
            'updated_balance' => 200.0,
            'billing_at' => 1735689600,
        ]);

        $this->assertDatabaseHas('ors.reports', [
            'ext_id' => 'payout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayerIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '131',
            'bet_amount' => 0,
            'bet_winlose' => 0,
            'created_at' => '2025-01-01 08:00:00',
            'updated_at' => '2025-01-01 08:00:00',
        ]);
    }

    public function test_credit_playerNotFound_expectedData()
    {
        $request = '{
            "transaction_id": "transaction_id",
            "secondary_info": {},
            "amount": 30,
            "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
            "other_info": {},
            "called_at": 1715052653,
            "remark": {},
            "bet_place": "BASEGAME",
            "transaction_type": "credit",
            "round_id": "uguhbkgvvu2gkn",
            "effective_amount": 250,
            "currency": "IDR",
            "winlose_amount": -220,
            "game_code": "pocketjungle",
            "timestamp": 1715052653,
            "player_id": "player_not_found",
            "game_id": 123,
            "signature": "9dd52fc7e55fd2e791933b62e8bf5e7c"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/credit',
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
            'rs_message' => 'player not available'
        ]);
    }

    public function test_credit_invalidSignature_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'playerID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "transaction_id": "transaction_id",
            "secondary_info": {},
            "amount": 30,
            "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
            "other_info": {},
            "called_at": 1715052653,
            "remark": {},
            "bet_place": "BASEGAME",
            "transaction_type": "credit",
            "round_id": "uguhbkgvvu2gkn",
            "effective_amount": 250,
            "currency": "IDR",
            "winlose_amount": -220,
            "game_code": "pocketjungle",
            "timestamp": 1715052653,
            "player_id": "playerID",
            "game_id": 123,
            "signature": "invalid signature"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/credit',
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

    public function test_credit_invalidPublicKeyHeader_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "transaction_id": "transaction_id",
            "secondary_info": {},
            "amount": 30,
            "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
            "other_info": {},
            "called_at": 1715052653,
            "remark": {},
            "bet_place": "BASEGAME",
            "transaction_type": "credit",
            "round_id": "uguhbkgvvu2gkn",
            "effective_amount": 250,
            "currency": "IDR",
            "winlose_amount": -220,
            "game_code": "pocketjungle",
            "timestamp": 1715052653,
            "player_id": "player_id",
            "game_id": 123,
            "signature": "8e1d0fb0c10064ebdb35f80edb50c624"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/credit',
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
    }

    public function test_credit_transactionNotFound_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "transaction_id": "transaction_not_found",
            "secondary_info": {},
            "amount": 30,
            "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
            "other_info": {},
            "called_at": 1715052653,
            "remark": {},
            "bet_place": "BASEGAME",
            "transaction_type": "credit",
            "round_id": "uguhbkgvvu2gkn",
            "effective_amount": 250,
            "currency": "IDR",
            "winlose_amount": -220,
            "game_code": "pocketjungle",
            "timestamp": 1715052653,
            "player_id": "player_id",
            "game_id": 123,
            "signature": "4f9d18814e894b901c7987a431404fef"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/credit',
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
            'rs_message' => 'transaction does not existed'
        ]);
    }

    public function test_credit_invalidWalletResponse_expectedData()
    {
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
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('ors.players')->insert([
            'play_id' => 'testPlayeru1',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        DB::table('ors.reports')->insert([
            'ext_id' => 'wager-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayeru1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => 131,
            'bet_amount' => 100.0,
            'bet_winlose' => 0,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00',
        ]);

        $request = '{
            "transaction_id": "testTransactionID",
            "secondary_info": {},
            "amount": 30,
            "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
            "other_info": {},
            "called_at": 1715052653,
            "remark": {},
            "bet_place": "BASEGAME",
            "transaction_type": "credit",
            "round_id": "testTransactionID",
            "effective_amount": 250,
            "currency": "IDR",
            "winlose_amount": -220,
            "game_code": "pocketjungle",
            "timestamp": 1715052653,
            "player_id": "testPlayeru1",
            "game_id": 131,
            "signature": "bf5e10adc7157d61a51ef16f215c9c09"
        }';

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/credit',
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
            'ext_id' => 'payout-testTransactionID',
            'round_id' => 'testTransactionID',
            'username' => 'testUsername',
            'play_id' => 'testPlayerIDu1',
            'web_id' => 1,
            'currency' => 'IDR',
            'game_code' => '131',
            'bet_amount' => 0,
            'bet_winlose' => -70.0,
            'created_at' => '2025-01-01 08:00:00.000',
            'updated_at' => '2025-01-01 08:00:00.000',
        ]);
    }

    #[DataProvider('creditParams')]
    public function test_credit_invalidRequest_expectedData($request)
    {

        $response = $this->call(
            'POST',
            '/ors/prov/api/v2/operator/transaction/credit',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'E-104',
            'rs_message' => 'invalid parameter',
        ]);
    }

    public static function creditParams()
    {
        return [
            [
                '{
                    "transaction_id": "transaction_id",
                    "secondary_info": {},
                    "amount": 30,
                    "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
                    "other_info": {},
                    "called_at": 1715052653,
                    "remark": {},
                    "bet_place": "BASEGAME",
                    "transaction_type": "credit",
                    "round_id": "uguhbkgvvu2gkn",
                    "effective_amount": 250,
                    "currency": "IDR",
                    "winlose_amount": -220,
                    "game_code": "pocketjungle",
                    "timestamp": 1715052653,
                    "game_id": 123,
                    "signature": "f453c64e0a564c8bb1fa0dd138852fcf"
                }'
            ],
            [
                '{
                    "transaction_id": "transaction_id",
                    "secondary_info": {},
                    "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
                    "other_info": {},
                    "called_at": 1715052653,
                    "remark": {},
                    "bet_place": "BASEGAME",
                    "transaction_type": "credit",
                    "round_id": "uguhbkgvvu2gkn",
                    "effective_amount": 250,
                    "currency": "IDR",
                    "winlose_amount": -220,
                    "game_code": "pocketjungle",
                    "timestamp": 1715052653,
                    "player_id": "player_id",
                    "game_id": 123,
                    "signature": "83c87d35ca8f0208a0d2637dffc13647"
                }'
            ],
            [
                '{
                    "transaction_id": "transaction_id",
                    "secondary_info": {},
                    "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
                    "other_info": {},
                    "called_at": 1715052653,
                    "remark": {},
                    "bet_place": "BASEGAME",
                    "transaction_type": "credit",
                    "round_id": "uguhbkgvvu2gkn",
                    "effective_amount": 250,
                    "currency": "IDR",
                    "winlose_amount": -220,
                    "game_code": "pocketjungle",
                    "timestamp": 1715052653,
                    "player_id": "player_id",
                    "game_id": 123,
                    "signature": "83c87d35ca8f0208a0d2637dffc13647"
                }'
            ],
            [
                '{
                    "transaction_id": "transaction_id",
                    "secondary_info": {},
                    "amount": 30,
                    "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
                    "other_info": {},
                    "remark": {},
                    "bet_place": "BASEGAME",
                    "transaction_type": "credit",
                    "round_id": "uguhbkgvvu2gkn",
                    "effective_amount": 250,
                    "currency": "IDR",
                    "winlose_amount": -220,
                    "game_code": "pocketjungle",
                    "timestamp": 1715052653,
                    "player_id": "player_id",
                    "game_id": 123,
                    "signature": "c615ce52498e02a1dc3b2d1a4fcaef5b"
                }'
            ],
            [
                '{
                    "transaction_id": "transaction_id",
                    "secondary_info": {},
                    "amount": 30,
                    "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
                    "other_info": {},
                    "called_at": 1715052653,
                    "remark": {},
                    "bet_place": "BASEGAME",
                    "transaction_type": "credit",
                    "round_id": "uguhbkgvvu2gkn",
                    "effective_amount": 250,
                    "winlose_amount": -220,
                    "game_code": "pocketjungle",
                    "timestamp": 1715052653,
                    "player_id": "player_id",
                    "game_id": 123,
                    "signature": "8dcc6f3ba5ec384ee833ccce473535f3"
                }'
            ],
            [
                '{
                    "transaction_id": "transaction_id",
                    "secondary_info": {},
                    "amount": 30,
                    "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
                    "other_info": {},
                    "called_at": 1715052653,
                    "remark": {},
                    "bet_place": "BASEGAME",
                    "transaction_type": "credit",
                    "effective_amount": 250,
                    "currency": "IDR",
                    "winlose_amount": -220,
                    "game_code": "pocketjungle",
                    "timestamp": 1715052653,
                    "player_id": "player_id",
                    "game_id": 123,
                    "signature": "e8b8d663598e0ea548c67f52df2ffa39"
                }'
            ],
            [
                '{
                    "transaction_id": "transaction_id",
                    "secondary_info": {},
                    "amount": 30,
                    "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
                    "other_info": {},
                    "called_at": 1715052653,
                    "remark": {},
                    "bet_place": "BASEGAME",
                    "transaction_type": "credit",
                    "round_id": "uguhbkgvvu2gkn",
                    "effective_amount": 250,
                    "currency": "IDR",
                    "winlose_amount": -220,
                    "game_code": "pocketjungle",
                    "timestamp": 1715052653,
                    "player_id": "player_id",
                    "signature": "92c99aa319db059a873479029b7b3110"
                }'
            ],
            [
                '{
                    "transaction_id": "transaction_id",
                    "secondary_info": {},
                    "amount": 30,
                    "result_url": "https://stage-slot-game.673ing.com/recallGame/?g=PocketJungle&t=bedee290-63d5-42e7-a2c9-31187ce7ca5f&b=uguhbkgvvu2gkn&c=true",
                    "other_info": {},
                    "called_at": 1715052653,
                    "remark": {},
                    "bet_place": "BASEGAME",
                    "round_id": "uguhbkgvvu2gkn",
                    "effective_amount": 250,
                    "currency": "IDR",
                    "winlose_amount": -220,
                    "game_code": "pocketjungle",
                    "timestamp": 1715052653,
                    "player_id": "player_id",
                    "game_id": 123,
                    "signature": "74312e4e84b463fd19497557b9899b4c"
                }'
            ],
        ];
    }
}
