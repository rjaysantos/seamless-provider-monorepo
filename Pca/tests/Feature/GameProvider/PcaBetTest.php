<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class PcaBetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_bet_validRequest_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'player001',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1100.0,
                    'status_code' => 2100
                ];
            }

            public function wagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'externalTransactionCode' => 'testTransactionCode',
            'externalTransactionDate' => '2021-01-01 00:00:00.000',
            'balance' => [
                'real' => 1000.00,
                'timestamp' => '2021-01-01 00:00:00.000'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_choice' => '-',
            'bet_id' => 'testRoundCode',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 08:00:00',
            'status' => 'WAGER',
            'ref_id' => 'testTransactionCode'
        ]);
    }

    public function test_bet_validRequestMultipleBet_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'player001',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_choice' => '-',
            'bet_id' => 'testRoundCode',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 08:00:00',
            'status' => 'WAGER',
            'ref_id' => 'testTransactionCode'
        ]);

        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode2',
            'transactionDate' => '2021-01-01 00:00:02.000',
            'amount' => '50',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 950.0,
                    'status_code' => 2100
                ];
            }

            public function wagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
            {
                return [
                    'credit_after' => 850.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'externalTransactionCode' => 'testTransactionCode2',
            'externalTransactionDate' => '2021-01-01 00:00:02.000',
            'balance' => [
                'real' => 850.00,
                'timestamp' => '2021-01-01 00:00:02.000'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_choice' => '-',
            'bet_id' => 'testRoundCode',
            'wager_amount' => 50,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 08:00:02',
            'status' => 'WAGER',
            'ref_id' => 'testTransactionCode2'
        ]);
    }

    #[DataProvider('betParams')]
    public function test_bet_invalidRequest_expectedData($parameter, $requestId)
    {
        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        unset($payload[$parameter]);

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => $requestId,
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ]);

        $response->assertStatus(200);
    }

    public static function betParams()
    {
        return [
            ['requestId', ''],
            ['username', 'testRequestID'],
            ['externalToken', 'testRequestID'],
            ['gameRoundCode', 'testRequestID'],
            ['transactionCode', 'testRequestID'],
            ['transactionDate', 'testRequestID'],
            ['amount', 'testRequestID'],
            ['gameCodeName', 'testRequestID'],
        ];
    }

    public function test_bet_playerNotFound_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_invalidPlayer',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_bet_usernameWithoutKiosk_expectedData()
    {
        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'invalidUsername',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_bet_transactionAlreadyExists_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_choice' => '-',
            'bet_id' => 'testRoundCode',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 08:00:00',
            'status' => 'WAGER',
            'ref_id' => 'testTransactionCode'
        ]);

        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 850.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'externalTransactionCode' => 'testTransactionCode',
            'externalTransactionDate' => '2021-01-01 00:00:00.000',
            'balance' => [
                'real' => 850.00,
                'timestamp' => '2021-01-01 00:00:00.000'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_bet_insufficientFund_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 10.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'error' => [
                'code' => 'ERR_INSUFFICIENT_FUNDS'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_bet_invalidToken_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'player001',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_invalidToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        $wallet = new class extends TestWallet {
            public function wagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'error' => [
                'code' => 'ERR_AUTHENTICATION_FAILED'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('pca.reports', [
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_choice' => '-',
            'bet_id' => 'testRoundCode',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 08:00:00',
            'status' => 'WAGER',
            'ref_id' => 'testTransactionCode'
        ]);
    }

    public function test_bet_invalidWalletResponseBalance_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 401
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'error' => [
                'code' => 'INTERNAL_ERROR'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_bet_invalidWalletResponseWagerAndPayout_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'player001',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1100.0,
                    'status_code' => 2100
                ];
            }

            public function wagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'error' => [
                'code' => 'INTERNAL_ERROR'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('pca.reports', [
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'testGameID',
            'bet_choice' => '-',
            'bet_id' => 'testRoundCode',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 08:00:00',
            'status' => 'WAGER',
            'ref_id' => 'testTransactionCode'
        ]);
    }

    #[DataProvider('walletAndExpectedAmount')]
    public function test_bet_validDataGiven_expectedData($wallet, $expectedBalance)
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'player001',
            'token' => 'PCAUCN_testToken',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'testRequestID',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_testToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'internalFundChanges' => [],
            'gameCodeName' => 'testGameID'
        ];

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/bet', $payload);

        $response->assertJson([
            'requestId' => 'testRequestID',
            'externalTransactionCode' => 'testTransactionCode',
            'externalTransactionDate' => '2021-01-01 00:00:00.000',
            'balance' => [
                'real' => $expectedBalance,
                'timestamp' => '2021-01-01 00:00:00.000'
            ]
        ]);

        $response->assertStatus(200);
    }

    public static function walletAndExpectedAmount()
    {
        return [
            [new class extends TestWallet {
                public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
                {
                    return ['credit_after' => 123, 'status_code' => 2100];
                }
            }, '123.00'],

            [new class extends TestWallet {
                public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
                {
                    return ['credit_after' => 123.456789, 'status_code' => 2100];
                }
            }, '123.45'],

            [new class extends TestWallet {
                public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
                {
                    return ['credit_after' => 123.409987, 'status_code' => 2100];
                }
            }, '123.40'],

            [new class extends TestWallet {
                public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
                {
                    return ['credit_after' => 123.000, 'status_code' => 2100];
                }
            }, '123.00'],

            [new class extends TestWallet {
                public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
                {
                    return ['credit_after' => 123.000009, 'status_code' => 2100];
                }
            }, '123.00'],

            [new class extends TestWallet {
                public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
                {
                    return ['credit_after' => 100.000, 'status_code' => 2100];
                }
            }, '100.00'],

            [new class extends TestWallet {
                public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
                {
                    return ['credit_after' => 100, 'status_code' => 2100];
                }
            }, '100.00'],
        ];
    }
}
