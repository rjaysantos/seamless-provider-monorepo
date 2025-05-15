<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\PlaPlayer;
use App\Models\PlaReport;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use App\GameProviders\Pla\PlaEncryption;
use App\GameProviders\Pla\PlaCredentials;

class PlaRefundTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE pla.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pla.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pla.playgame RESTART IDENTITY;');
        parent::tearDown();
    }

    private function createHash(array $request)
    {
        $credentialSetter = app()->make(PlaCredentials::class);
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'ref_id' => 'testTransactionID1'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'testGameID',
            'transaction_id' => 'testTransactionID1',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1100.00,
            'token' => 'testToken',
            'hash' => 'f760230471950ef95551e35456d8671c'
        ]);

        $this->assertDatabaseHas('pla.reports', [
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 100,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'ref_id' => 'testTransactionID1'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => -100,
            'game_name' => 'testGameID',
            'transaction_id' => 'testTransactionID1',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 900.00,
            'token' => 'testToken',
            'hash' => '2b7adfff1f6da1e50baed0dd93475aac'
        ]);

        $this->assertDatabaseHas('pla.reports', [
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => -100,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'ref_id' => 'refund-testTransactionID1',
        ]);

        $response->assertStatus(200);
    }

    public function test_refund_playerNotFound_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'invalidPlayID',
            'refund' => 100,
            'game_name' => 'testGameID',
            'transaction_id' => 'testTransactionID',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => 'testToken',
            'reason' => 'Member Not Exists',
            'hash' => 'fcea29b9fa318058cf4a2265e795b935'
        ]);

        $response->assertStatus(404);
    }

    public function test_refund_invalidHash_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'testGameID',
            'transaction_id' => 'testTransactionID',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = 'invalidHash';

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => 'testToken',
            'reason' => 'Invalid Signature',
            'hash' => 'e6927cb51997735916d8ab8bcf4adec1'
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'ref_id' => 'testTransactionID1',
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'testGameID',
            'transaction_id' => 'testTransactionID2',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'invalidRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 1000,
            'token' => 'testToken',
            'reason' => 'Internal Server Error',
            'hash' => '240b3e84ae99f758f6960c519d2162a9'
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'ref_id' => 'testTransactionID1'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 100,
            'created_at' => '2021-01-01 00:00:02',
            'updated_at' => '2021-01-01 00:00:02',
            'ref_id' => 'refund-testTransactionID1'
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'testGameID',
            'transaction_id' => 'testTransactionID1',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-01-01 00:00:04.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1100.00,
            'token' => 'testToken',
            'hash' => 'f760230471950ef95551e35456d8671c'
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'ref_id' => 'payout-1-testRoundID',
        ]);

        $payload = [
            'action' => 'refund_balance',
            'user_id' => 'testPlayID',
            'refund' => 100,
            'game_name' => 'testGameID',
            'transaction_id' => 'testTransactionID',
            'token' => 'testToken',
            'session_id' => 'testSessionID',
            'reason' => 'testReason',
            'round_id' => 'testRoundID',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2020-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 1000,
            'token' => 'testToken',
            'reason' => 'Internal Server Error',
            'hash' => '240b3e84ae99f758f6960c519d2162a9'
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => $payload['token'] ?? '',
            'reason' => 'Internal Server Error',
            'hash' => $hash
        ]);

        $response->assertStatus(500);
    }

    public static function refundParams()
    {
        return [
            ['user_id', 'a033d7b449c6c8c2f3bc00d7862ac4a8'],
            ['refund', 'a033d7b449c6c8c2f3bc00d7862ac4a8'],
            ['game_name', 'a033d7b449c6c8c2f3bc00d7862ac4a8'],
            ['transaction_id', 'a033d7b449c6c8c2f3bc00d7862ac4a8'],
            ['token', '57e3f51a79ec2152711684c60e6bfeec'],
            ['round_id', 'a033d7b449c6c8c2f3bc00d7862ac4a8'],
            ['game_round_close', 'a033d7b449c6c8c2f3bc00d7862ac4a8'],
            ['hash', 'a033d7b449c6c8c2f3bc00d7862ac4a8']
        ];
    }
}
