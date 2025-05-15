<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\PcaPlayer;
use App\Models\PcaReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use App\GameProviders\Pla\PlaEncryption;
use App\GameProviders\Pca\PcaCredentials;

class PcaRefundTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE pca.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.playgame RESTART IDENTITY;');
        parent::tearDown();
    }

    private function createHash(array $request)
    {
        $credentialSetter = app()->make(PcaCredentials::class);
        $encryptionLib = new PlaEncryption($credentialSetter->getCredentialsByCurrency(null));

        return $encryptionLib->generateHash($request);
    }

    public function test_refund_addAmountValidRequest_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Bonus($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Cancel($payload)
                        {
                            return 0.0;
                        }

                        public function Resettle($payload)
                        {
                            return [
                                'credit_after' => 1100.0,
                            ];
                        }

                        public function WagerAndPayout($payload, $report)
                        {
                            return 0.0;
                        }

                        public function TransferIn($payload)
                        {
                            return 0.0;
                        }

                        public function TransferOut($payload)
                        {
                            return 0.0;
                        }
                    };
                }
            };
        });

        Carbon::setTestNow('2021-01-01 00:00:00');

        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'ref_id' => 'testTransactionID1'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'test;testGameID',
            'transaction_id' => 'testTransactionID1',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1100.00,
            'token' => 'testToken',
            'hash' => '1b9052c793bc9a0703fc7f4174febae6'
        ]);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => 100,
            'ref_id' => 'refund-testTransactionID1'
        ]);

        $response->assertStatus(200);
    }

    public function test_refund_deductAmountValidRequest_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Bonus($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Cancel($payload)
                        {
                            return 0.0;
                        }

                        public function Resettle($payload)
                        {
                            return [
                                'credit_after' => 900.0,
                            ];
                        }

                        public function WagerAndPayout($payload, $report)
                        {
                            return 0.0;
                        }

                        public function TransferIn($payload)
                        {
                            return 0.0;
                        }

                        public function TransferOut($payload)
                        {
                            return 0.0;
                        }
                    };
                }
            };
        });

        Carbon::setTestNow('2021-01-01 00:00:00');

        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'ref_id' => 'testTransactionID1'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => -100,
            'game_name' => 'test;testGameID',
            'transaction_id' => 'testTransactionID1',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 900.00,
            'token' => 'testToken',
            'hash' => '90e5a353c107e20cc37c5ba84c22fff1'
        ]);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => -100,
            'ref_id' => 'refund-testTransactionID1'
        ]);

        $response->assertStatus(200);
    }

    public function test_refund_playerNotFound_expectedData()
    {
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'invalidPlayID',
            'refund' => 100,
            'game_name' => 'test;testGameID',
            'transaction_id' => 'testTransactionID',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => 'testToken',
            'reason' => 'Member Not Exists',
            'hash' => '446837782664787c4d26f064c736c785'
        ]);

        $response->assertStatus(404);
    }

    public function test_refund_invalidHash_expectedData()
    {
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'test;testGameID',
            'transaction_id' => 'testTransactionID',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = 'invalidHash';

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => 'testToken',
            'reason' => 'Invalid Signature',
            'hash' => '8584a274cb5180eba5da1491b5168dd3'
        ]);

        $response->assertStatus(401);
    }

    public function test_refund_transactionNotFound_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Bonus($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Cancel($payload)
                        {
                            return 0.0;
                        }

                        public function Resettle($payload)
                        {
                            return 0.0;
                        }

                        public function WagerAndPayout($payload, $report)
                        {
                            return 0.0;
                        }

                        public function TransferIn($payload)
                        {
                            return;
                        }

                        public function TransferOut($payload)
                        {
                            return 0.0;
                        }
                    };
                }
            };
        });

        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'ref_id' => 'testTransactionID1'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'test;testGameID',
            'transaction_id' => 'testTransactionID',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'invalidRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 1000,
            'token' => 'testToken',
            'reason' => 'Internal Server Error',
            'hash' => 'ca0392cba87ca6de6593208157cccb6c'
        ]);

        $response->assertStatus(500);
    }

    public function test_refund_transactionAlreadyRefunded_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 1100.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Bonus($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Cancel($payload)
                        {
                            return 0.0;
                        }

                        public function Resettle($payload)
                        {
                            return [
                                'credit_after' => 1100.0,
                            ];
                        }

                        public function WagerAndPayout($payload, $report)
                        {
                            return 0.0;
                        }

                        public function TransferIn($payload)
                        {
                            return 0.0;
                        }

                        public function TransferOut($payload)
                        {
                            return 0.0;
                        }
                    };
                }
            };
        });

        Carbon::setTestNow('2021-01-01 00:00:04');

        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'ref_id' => 'testTransactionID1'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:02',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => 100,
            'ref_id' => 'refund-testTransactionID1'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'test;testGameID',
            'transaction_id' => 'testTransactionID1',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-01-01 00:00:04.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1100.00,
            'token' => 'testToken',
            'hash' => '1b9052c793bc9a0703fc7f4174febae6'
        ]);

        $response->assertStatus(200);
    }

    public function test_refund_invalidWalletResponse_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 1000.0,
                            ];
                        }

                        public function Wager($payload, $report)
                        {
                            return 0.0;
                        }

                        public function Payout($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Bonus($payload, $report, bool $isMustWait = false)
                        {
                            return 0.0;
                        }

                        public function Cancel($payload)
                        {
                            return 0.0;
                        }

                        public function Resettle($payload)
                        {
                            return null;
                        }

                        public function WagerAndPayout($payload, $report)
                        {
                            return 0.0;
                        }

                        public function TransferIn($payload)
                        {
                            return 0.0;
                        }

                        public function TransferOut($payload)
                        {
                            return 0.0;
                        }
                    };
                }
            };
        });

        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'ref_id' => 'testTransactionID1'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'test;testGameID',
            'transaction_id' => 'testTransactionID',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 1000,
            'token' => 'testToken',
            'reason' => 'Internal Server Error',
            'hash' => 'ca0392cba87ca6de6593208157cccb6c'
        ]);

        $response->assertStatus(500);
    }

    /**
     * @dataProvider refundParams
     */
    public function test_refund_invalidRequest_expectedData($params, $hash)
    {
        $payload = [
            'user_id' => 'update_balance',
            'refund' => 'testPlayID',
            'game_name' => '100',
            'transaction_id' => '0',
            'token' => 'testGameID',
            'round_id' => 'testTransactionID',
            'game_round_close' => 'testRoundID',
        ];

        if ($params != 'hash') {
            unset($payload[$params]);
            $payload['hash'] = $this->createHash($payload);
        }

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => $payload['token'] ?? '',
            'reason' => 'Internal Server Error',
            'hash' => $hash,
        ]);

        $response->assertStatus(500);
    }

    public static function refundParams()
    {
        return [
            ['user_id', '088d447b851394b50849964dfd37b5b6'],
            ['refund', '088d447b851394b50849964dfd37b5b6'],
            ['game_name', '088d447b851394b50849964dfd37b5b6'],
            ['transaction_id', '088d447b851394b50849964dfd37b5b6'],
            ['token', '63fbaf92c987cc40a7683092c62aaecf'],
            ['round_id', '088d447b851394b50849964dfd37b5b6'],
            ['game_round_close', '088d447b851394b50849964dfd37b5b6'],
            ['hash', '088d447b851394b50849964dfd37b5b6']
        ];
    }
}
