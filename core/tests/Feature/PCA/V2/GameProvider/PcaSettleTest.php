<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class PcaSettleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_settle_validRequestNoWin_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'WAGER',
            'ref_id' => '8366794150'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
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

        Carbon::setTestNow('2024-01-01 00:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionCode' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionDate' => '2023-12-31 16:00:00.000',
            'balance' => [
                'real' => '1000.00',
                'timestamp' => '2023-12-31 16:00:00.000'
            ]
        ]);

        Carbon::setTestNow();

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 0,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'PAYOUT',
            'ref_id' => 'L-b0f09415-8eec-493d-8e70-c0659b972653'
        ]);
    }

    public function test_settle_validRequestWithWin_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'WAGER',
            'ref_id' => '8366794150'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN',
                'internalFundChanges' => []
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
            public function WagerAndPayout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $wagerTransactionID,
                float $wagerAmount,
                string $payoutTransactionID,
                float $payoutAmount,
                Report $report
            ): array {
                return [
                    'credit_after' => 1010.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionCode' => '8366794157',
            'externalTransactionDate' => '2024-01-01 00:00:03.000',
            'balance' => [
                'real' => '1010.00',
                'timestamp' => '2024-01-01 00:00:03.000'
            ]
        ]);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 0,
            'payout_amount' => 10,
            'bet_time' => '2024-01-01 08:00:03',
            'status' => 'PAYOUT',
            'ref_id' => '8366794157'
        ]);
    }

    #[DataProvider('gameRoundResultNoWinParams')]
    public function test_settle_invalidRequestNoWin_expectedData($unset, $requestId)
    {
        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
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

        unset($payload[$unset]);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => $requestId,
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ]);
    }

    public static function gameRoundResultNoWinParams()
    {
        return [
            ['requestId', ''],
            ['username', 'b0f09415-8eec-493d-8e70-c0659b972653'],
            ['gameRoundCode', 'b0f09415-8eec-493d-8e70-c0659b972653'],
            ['gameCodeName', 'b0f09415-8eec-493d-8e70-c0659b972653'],
        ];
    }

    #[DataProvider('gameRoundResultWithWinParams')]
    public function test_settle_invalidRequestWithWin_expectedData($unset, $requestId)
    {
        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN',
                'internalFundChanges' => []
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

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => $requestId,
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ]);
    }

    public static function gameRoundResultWithWinParams()
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

    public function test_settle_playerNotFound_expectedData()
    {
        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN',
                'internalFundChanges' => []
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

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);
    }

    public function test_settle_usernameWithoutKiosk_expectedData()
    {
        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'invalidUsername',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN',
                'internalFundChanges' => []
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

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);
    }

    public function test_settle_NoBetTransactionFound_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN',
                'internalFundChanges' => []
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

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'error' => [
                'code' => 'INTERNAL_ERROR'
            ]
        ]);
    }

    public function test_settle_transactionAlreadyExistWithoutWin_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'WAGER',
            'ref_id' => '8366794150'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 0,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'PAYOUT',
            'ref_id' => 'L-b0f09415-8eec-493d-8e70-c0659b972653'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
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

        Carbon::setTestNow('2024-01-01 00:00:00.000');

        $wallet = new class extends TestWallet {
            public function Balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionCode' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionDate' => '2023-12-31 16:00:00.000',
            'balance' => [
                'real' => '1000.00',
                'timestamp' => '2023-12-31 16:00:00.000'
            ]
        ]);

        Carbon::setTestNow();
    }

    public function test_settle_transactionAlreadyExistWithWin_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 08:00:00',
            'status' => 'WAGER',
            'ref_id' => '8366794150'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 0,
            'payout_amount' => 10,
            'bet_time' => '2024-01-01 08:00:03',
            'status' => 'PAYOUT',
            'ref_id' => '8366794157'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN',
                'internalFundChanges' => []
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
            public function WagerAndPayout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $wagerTransactionID,
                float $wagerAmount,
                string $payoutTransactionID,
                float $payoutAmount,
                Report $report
            ): array {
                return [
                    'credit_after' => 1010.0,
                    'status_code' => 2102
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionCode' => '8366794157',
            'externalTransactionDate' => '2024-01-01 00:00:00.000',
            'balance' => [
                'real' => '1010.00',
                'timestamp' => '2024-01-01 00:00:00.000'
            ]
        ]);
    }

    public function test_settle_invalidWalletResponse_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'WAGER',
            'ref_id' => '8366794150'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN',
                'internalFundChanges' => []
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
            public function WagerAndPayout(
                IWalletCredentials $credentials,
                string $playID,
                string $currency,
                string $wagerTransactionID,
                float $wagerAmount,
                string $payoutTransactionID,
                float $payoutAmount,
                Report $report
            ): array {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('pca/prov/gameroundresult', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            "requestId" => 'b0f09415-8eec-493d-8e70-c0659b972653',
            "error" => [
                "code" => "INTERNAL_ERROR"
            ]
        ]);

        $this->assertDatabaseMissing('pca.reports', [
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 0,
            'payout_amount' => 10,
            'bet_time' => '2024-01-01 00:00:03',
            'status' => 'PAYOUT',
            'ref_id' => '8366794157'
        ]);
    }

    #[DataProvider('walletAndExpectedAmount')]
    public function test_settle_validDataGiven_expectedData($wallet, $expectedBalance)
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.reports')->insert([
            'play_id' => 'player001',
            'currency' => 'IDR',
            'game_code' => 'aogs',
            'bet_choice' => '-',
            'bet_id' => '27281386',
            'wager_amount' => 10,
            'payout_amount' => 0,
            'bet_time' => '2024-01-01 00:00:00',
            'status' => 'WAGER',
            'ref_id' => '8366794150'
        ]);

        $payload = [
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888',
            'gameRoundCode' => '27281386',
            'pay' => [
                'transactionCode' => '8366794157',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN',
                'internalFundChanges' => []
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

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'b0f09415-8eec-493d-8e70-c0659b972653',
            'externalTransactionCode' => '8366794157',
            'externalTransactionDate' => '2024-01-01 00:00:00.000',
            'balance' => [
                'real' => $expectedBalance,
                'timestamp' => '2024-01-01 00:00:00.000'
            ]
        ]);
    }

    public static function walletAndExpectedAmount()
    {
        return [
            [new class extends TestWallet {
                public function WagerAndPayout(
                    IWalletCredentials $credentials,
                    string $playID,
                    string $currency,
                    string $wagerTransactionID,
                    float $wagerAmount,
                    string $payoutTransactionID,
                    float $payoutAmount,
                    Report $report
                ): array {
                    return ['credit_after' => 123, 'status_code' => 2100];
                }
            }, '123.00'],

            [new class extends TestWallet {
                public function WagerAndPayout(
                    IWalletCredentials $credentials,
                    string $playID,
                    string $currency,
                    string $wagerTransactionID,
                    float $wagerAmount,
                    string $payoutTransactionID,
                    float $payoutAmount,
                    Report $report
                ): array {
                    return ['credit_after' => 123.456789, 'status_code' => 2100];
                }
            },'123.45'],

            [new class extends TestWallet {
                public function WagerAndPayout(
                    IWalletCredentials $credentials,
                    string $playID,
                    string $currency,
                    string $wagerTransactionID,
                    float $wagerAmount,
                    string $payoutTransactionID,
                    float $payoutAmount,
                    Report $report
                ): array {
                    return ['credit_after' => 123.409987, 'status_code' => 2100];
                }
            }, '123.40'],

            [new class extends TestWallet {
                public function WagerAndPayout(
                    IWalletCredentials $credentials,
                    string $playID,
                    string $currency,
                    string $wagerTransactionID,
                    float $wagerAmount,
                    string $payoutTransactionID,
                    float $payoutAmount,
                    Report $report
                ): array {
                    return ['credit_after' => 123.000, 'status_code' => 2100];
                }
            }, '123.00'],

            [new class extends TestWallet {
                public function WagerAndPayout(
                    IWalletCredentials $credentials,
                    string $playID,
                    string $currency,
                    string $wagerTransactionID,
                    float $wagerAmount,
                    string $payoutTransactionID,
                    float $payoutAmount,
                    Report $report
                ): array {
                    return ['credit_after' => 123.000009, 'status_code' => 2100];
                }
            }, '123.00'],

            [new class extends TestWallet {
                public function WagerAndPayout(
                    IWalletCredentials $credentials,
                    string $playID,
                    string $currency,
                    string $wagerTransactionID,
                    float $wagerAmount,
                    string $payoutTransactionID,
                    float $payoutAmount,
                    Report $report
                ): array {
                    return ['credit_after' => 100.000, 'status_code' => 2100];
                }
            }, '100.00'],

            [new class extends TestWallet {
                public function WagerAndPayout(
                    IWalletCredentials $credentials,
                    string $playID,
                    string $currency,
                    string $wagerTransactionID,
                    float $wagerAmount,
                    string $payoutTransactionID,
                    float $payoutAmount,
                    Report $report
                ): array {
                    return ['credit_after' => 100, 'status_code' => 2100];
                }
            }, '100.00'],
        ];
    }
}