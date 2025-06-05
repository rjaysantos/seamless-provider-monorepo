<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use Wallet\V1\ProvSys\Transfer\Report;

class PcaRefundTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY');
        DB::statement('TRUNCATE TABLE pca.reports RESTART IDENTITY');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_refund_validRequest_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testplayid',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '1234567890',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'WAGER',
            'ref_id' => '27281386'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'internalFundChanges' => [],
                'relatedTransactionCode' => '1234567890'
            ],
            'jackpot' => [
                'contributionAmount' => '0.0123456789123456',
                'winAmount' => '0',
                'jackpotId' => 'mrj_830_840_850_860_306'
            ],
            'gameRoundClose' => [
                'date' => '2024-01-01 00:00:00.000',
                'rngGeneratorId' => 'Casino Protego SG100',
                'rngSoftwareId' => 'Casino CaGS 20.6.2.0'
            ],
            'gameCodeName' => 'aogs'
        ];

        $wallet = new class extends TestWallet {
            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
            {
                return [
                    'credit_after' => 1010.0,
                    'status_code' => 2100
                ];
                  
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionCode' => '8366794157',
            'externalTransactionDate' => '2024-01-01 00:00:00.000',
            'balance' => [
                'real' => '1010.00',
                'timestamp' => '2024-01-01 00:00:00.000'
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '8366794157',
            'wager_amount' => 10,
            'payout_amount' => 10,
            'bet_time' => '2024-01-01 08:00:00',
            'status' => 'REFUND',
            'ref_id' => '1234567890'
        ]);
    }

    #[DataProvider('gameRoundResultRefundParams')]
    public function test_refund_invalidRequest_expectedData($unset, $requestId)
    {
        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'internalFundChanges' => [],
                'relatedTransactionCode' => '1234567890'
            ],
            'jackpot' => [
                'contributionAmount' => '0.0123456789123456',
                'winAmount' => '0',
                'jackpotId' => 'mrj_830_840_850_860_306'
            ],
            'gameRoundClose' => [
                'date' => '2024-01-01 00:00:00.000',
                'rngGeneratorId' => 'Casino Protego SG100',
                'rngSoftwareId' => 'Casino CaGS 20.6.2.0'
            ],
            'gameCodeName' => 'aogs'
        ];

        if (isset($payload[$unset]) === true)
            unset($payload[$unset]);
        else
            unset($payload['pay'][$unset]);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertJson([
            'requestId' => $requestId,
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ]);

        $response->assertStatus(200);
    }

    public static function gameRoundResultRefundParams()
    {
        return [
            ['requestId', ''],
            ['username', 'b0f09415-8eec-493d-8e70-c0659b972653'],
            ['gameRoundCode', 'b0f09415-8eec-493d-8e70-c0659b972653'],
            ['transactionCode', 'b0f09415-8eec-493d-8e70-c0659b972653'],
            ['transactionDate', 'b0f09415-8eec-493d-8e70-c0659b972653'],
            ['amount', 'b0f09415-8eec-493d-8e70-c0659b972653'],
            ['type', 'b0f09415-8eec-493d-8e70-c0659b972653'],
            ['gameCodeName', 'b0f09415-8eec-493d-8e70-c0659b972653']
        ];
    }

    public function test_refund_playerNotFound_expectedData()
    {
        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'internalFundChanges' => [],
                'relatedTransactionCode' => '1234567890'
            ],
            'jackpot' => [
                'contributionAmount' => '0.0123456789123456',
                'winAmount' => '0',
                'jackpotId' => 'mrj_830_840_850_860_306'
            ],
            'gameRoundClose' => [
                'date' => '2024-01-01 00:00:00.000',
                'rngGeneratorId' => 'Casino Protego SG100',
                'rngSoftwareId' => 'Casino CaGS 20.6.2.0'
            ],
            'gameCodeName' => 'aogs'
        ];

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_refund_usernameWithoutKiosk_expectedData()
    {
        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'invalidUsername',
            'externalToken' => 'PCAUCN_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'internalFundChanges' => [],
                'relatedTransactionCode' => '1234567890'
            ],
            'jackpot' => [
                'contributionAmount' => '0.0123456789123456',
                'winAmount' => '0',
                'jackpotId' => 'mrj_830_840_850_860_306'
            ],
            'gameRoundClose' => [
                'date' => '2024-01-01 00:00:00.000',
                'rngGeneratorId' => 'Casino Protego SG100',
                'rngSoftwareId' => 'Casino CaGS 20.6.2.0'
            ],
            'gameCodeName' => 'aogs'
        ];

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_refund_transactionNotFound_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testplayid',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'internalFundChanges' => [],
                'relatedTransactionCode' => '1234567890'
            ],
            'jackpot' => [
                'contributionAmount' => '0.0123456789123456',
                'winAmount' => '0',
                'jackpotId' => 'mrj_830_840_850_860_306'
            ],
            'gameRoundClose' => [
                'date' => '2024-01-01 00:00:00.000',
                'rngGeneratorId' => 'Casino Protego SG100',
                'rngSoftwareId' => 'Casino CaGS 20.6.2.0'
            ],
            'gameCodeName' => 'aogs'
        ];

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'error' => [
                'code' => 'ERR_NO_BET'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_refund_transactionAlreadyExist_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testplayid',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '1234567890',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'WAGER',
            'ref_id' => '27281386'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '8366794157',
            'wager_amount' => 10,
            'payout_amount' => 10,
            'bet_time' => '2024-01-01 08:00:00',
            'status' => 'REFUND',
            'ref_id' => '1234567890'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'internalFundChanges' => [],
                'relatedTransactionCode' => '1234567890'
            ],
            'jackpot' => [
                'contributionAmount' => '0.0123456789123456',
                'winAmount' => '0',
                'jackpotId' => 'mrj_830_840_850_860_306'
            ],
            'gameRoundClose' => [
                'date' => '2024-01-01 00:00:00.000',
                'rngGeneratorId' => 'Casino Protego SG100',
                'rngSoftwareId' => 'Casino CaGS 20.6.2.0'
            ],
            'gameCodeName' => 'aogs'
        ];

        $wallet = new class extends TestWallet {
            public function Balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1010.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionCode' => '8366794157',
            'externalTransactionDate' => '2024-01-01 00:00:00.000',
            'balance' => [
                'real' => '1010.00',
                'timestamp' => '2024-01-01 00:00:00.000'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_refund_invalidWalletResponse_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testplayid',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '1234567890',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'WAGER',
            'ref_id' => '27281386'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'internalFundChanges' => [],
                'relatedTransactionCode' => '1234567890'
            ],
            'jackpot' => [
                'contributionAmount' => '0.0123456789123456',
                'winAmount' => '0',
                'jackpotId' => 'mrj_830_840_850_860_306'
            ],
            'gameRoundClose' => [
                'date' => '2024-01-01 00:00:00.000',
                'rngGeneratorId' => 'Casino Protego SG100',
                'rngSoftwareId' => 'Casino CaGS 20.6.2.0'
            ],
            'gameCodeName' => 'aogs'
        ];

        $wallet = new class extends TestWallet {
            public function WagerAndPayout(IWalletCredentials $credentials, string $playID, string $currency, string $wagerTransactionID, float $wagerAmount, string $payoutTransactionID, float $payoutAmount, Report $report): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertJson([
            "requestId" => 'b0f09415-8eec-493d-8e70-c0659b972653',
            "error" =>  [
                "code" => "INTERNAL_ERROR"
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('pca.reports', [
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '8366794157',
            'wager_amount' => 10,
            'payout_amount' => 10,
            'bet_time' => '2024-01-01 08:00:00',
            'status' => 'REFUND',
            'ref_id' => '1234567890'
        ]);
    }

    #[DataProvider('walletAndExpectedAmount')]
    public function test_refund_validDataGiven_expectedData($wallet, $expectedBalance)
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testplayid',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'testplayid',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '1234567890',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'WAGER',
            'ref_id' => '27281386'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'internalFundChanges' => [],
                'relatedTransactionCode' => '1234567890'
            ],
            'jackpot' => [
                'contributionAmount' => '0.0123456789123456',
                'winAmount' => '0',
                'jackpotId' => 'mrj_830_840_850_860_306'
            ],
            'gameRoundClose' => [
                'date' => '2024-01-01 00:00:00.000',
                'rngGeneratorId' => 'Casino Protego SG100',
                'rngSoftwareId' => 'Casino CaGS 20.6.2.0'
            ],
            'gameCodeName' => 'aogs'
        ];

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionCode' => '8366794157',
            'externalTransactionDate' => '2024-01-01 00:00:00.000',
            'balance' => [
                'real' => $expectedBalance,
                'timestamp' => '2024-01-01 00:00:00.000'
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
