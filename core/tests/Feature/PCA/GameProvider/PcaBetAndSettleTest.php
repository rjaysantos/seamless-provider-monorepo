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

class PcaBetAndSettleTest extends TestCase
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

    public function test_betAndSettle_betValidRequest_expectedData()
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
                            return [
                                'credit_after' => 900.0,
                            ];
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

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '100',
            'win' => '0',
            'jackpot_win' => '0',
            'game_name' => 'test;testGameID',
            'free_spin' =>
                [
                    'win' => 0,
                    'played' => 0,
                    'remained' => 0
                ],
            'transaction_id' => 'testTransactionID',
            'round_id' => 'testRoundID',
            'session_id' => 'testSessionID',
            'token' => 'testToken',
            'request_token' => 'testRequestToken'
        ];

        Carbon::setTestNow('2021-01-01 00:00:00');

        $hash = $this->createHash($payload);

        $payload['hash'] = $hash;

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 900.00,
            'token' => 'testToken',
            'hash' => '90e5a353c107e20cc37c5ba84c22fff1',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID'
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_addBetValidRequest_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 900.0,
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
                            return [
                                'credit_after' => 700.0
                            ];
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
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '200',
            'win' => '0',
            'jackpot_win' => '0',
            'game_name' => 'test;testGameID',
            'free_spin' =>
                [
                    'win' => 0,
                    'played' => 0,
                    'remained' => 0
                ],
            'transaction_id' => 'testTransactionID2',
            'round_id' => 'testRoundID',
            'session_id' => 'testSessionID',
            'token' => 'testToken',
            'request_token' => 'testRequestToken'
        ];

        Carbon::setTestNow('2021-01-01 00:00:10');

        $hash = $this->createHash($payload);

        $payload['hash'] = $hash;

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 700.00,
            'token' => 'testToken',
            'hash' => 'b7e26925da6d0341b31a4acdee3e699b',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:10',
            'bet_id' => 'testRoundID',
            'wager_amount' => 200,
            'payout_amount' => 0,
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID2'
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_settleValidRequest_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
                        public function Balance($payload)
                        {
                            return [
                                'credit' => 800.0,
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
                            return [
                                'credit_after' => 1000.0
                            ];
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
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '0',
            'win' => '200',
            'jackpot_win' => '0',
            'game_name' => 'test;testGameID',
            'free_spin' =>
                [
                    'win' => 0,
                    'played' => 0,
                    'remained' => 0
                ],
            'transaction_id' => 'testTransactionID3',
            'round_id' => 'testRoundID',
            'session_id' => 'testSessionID',
            'token' => 'testToken',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2022-06-25 05:59:08.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        Carbon::setTestNow('2021-01-01 00:00:20');

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'token' => 'testToken',
            'hash' => '4a02f72cddb37bfee9b2713818540868',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:20',
            'bet_id' => 'testRoundID',
            'wager_amount' => 0,
            'payout_amount' => 200,
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID3'
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_addSettleValidRequest_expectedData()
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
                            return [
                                'credit_after' => 1100.0,
                            ];
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
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 200,
            'payout_amount' => 0,
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID2'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:20',
            'bet_id' => 'testRoundID',
            'wager_amount' => 0,
            'payout_amount' => 200,
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID3'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '0',
            'win' => '100',
            'jackpot_win' => '0',
            'game_name' => 'test;testGameID',
            'free_spin' =>
                [
                    'win' => 0,
                    'played' => 0,
                    'remained' => 0
                ],
            'transaction_id' => 'testTransactionID4',
            'round_id' => 'testRoundID',
            'session_id' => 'testSessionID',
            'token' => 'testToken',
            'request_token' => 'testRequestToken',
            'game_round_close' => '2022-06-25 05:59:10.107',
        ];

        $payload['hash'] = $this->createHash($payload);

        Carbon::setTestNow('2021-01-01 00:00:30');

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1100.00,
            'token' => 'testToken',
            'hash' => '1b9052c793bc9a0703fc7f4174febae6',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:30',
            'bet_id' => 'testRoundID',
            'wager_amount' => 0,
            'payout_amount' => 100,
            'ref_id' => 'testTransactionID4'
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_bonusValidRequest_expectedData()
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
                            return [
                                'credit_after' => 1000.0,
                            ];
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
            'play_id' => 'dzrw9r8nvu027',
            'currency' => 'IDR'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'dzrw9r8nvu027',
            'currency' => 'IDR',
            'game_code' => 'rol;rol_autoroulette2',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:00',
            'bet_id' => '80087673-4905-29b8-04f3-c9401dcda78c',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'status' => 'PAYOUT',
            'ref_id' => 'ed6fa4c8-e023-b697-4f6e-c0b79333a424'
        ]);

        $payload = [
            "action" => "update_balance",
            "user_id" => "dzrw9r8nvu027",
            "bet" => "0",
            "win" => "0",
            "jackpot_win" => "0",
            "game_name" => "rol;rol_autoroulette2",
            "free_spin" => [
                "win" => 0,
                "played" => 1,
                "remained" => 4
            ],
            "transaction_id" => "ed6fa4c8-e023-b697-4f6e-c0b79333a242",
            "session_id" => "6579780b-4564-181b-1a02-40882beae5fe",
            "round_id" => "80087673-4905-29b8-04f3-c9401dcda78c",
            "token" => "8dbwE5NWyrHahQZqWVZg83FWMMUChVLA",
            "request_token" => "hYj8xWvc99hTvieA0t996HThEAGyKwkI",
            "hash" => "c58e400fe5ab703f7064730109c179ca"
        ];

        $payload['hash'] = $this->createHash($payload);

        Carbon::setTestNow('2021-01-01 00:00:30');

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'token' => '8dbwE5NWyrHahQZqWVZg83FWMMUChVLA',
            'hash' => 'e53e0fdb50b768b9db228138c8a4feaa',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'dzrw9r8nvu027',
            'currency' => 'IDR',
            'game_code' => 'rol;rol_autoroulette2',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:30',
            'bet_id' => '80087673-4905-29b8-04f3-c9401dcda78c',
            'wager_amount' => 0,
            'payout_amount' => 0,
            'status' => 'PAYOUT',
            'ref_id' => 'ed6fa4c8-e023-b697-4f6e-c0b79333a242'
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_bonusAtStartOfRoundValidRequest_expectedData()
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
                            return [
                                'credit_after' => 1000.0,
                            ];
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
            'play_id' => 'dzrw9r8nvu027',
            'currency' => 'IDR'
        ]);

        $payload = [
            "action" => "update_balance",
            "user_id" => "dzrw9r8nvu027",
            "bet" => "0",
            "win" => "0",
            "jackpot_win" => "0",
            "game_name" => "rol;rol_autoroulette2",
            "free_spin" => [
                "win" => 0,
                "played" => 1,
                "remained" => 4
            ],
            "transaction_id" => "ed6fa4c8-e023-b697-4f6e-c0b79333a242",
            "session_id" => "6579780b-4564-181b-1a02-40882beae5fe",
            "round_id" => "80087673-4905-29b8-04f3-c9401dcda78c",
            "token" => "8dbwE5NWyrHahQZqWVZg83FWMMUChVLA",
            "request_token" => "hYj8xWvc99hTvieA0t996HThEAGyKwkI",
            "hash" => "c58e400fe5ab703f7064730109c179ca"
        ];

        $payload['hash'] = $this->createHash($payload);

        Carbon::setTestNow('2021-01-01 00:00:30');

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'token' => '8dbwE5NWyrHahQZqWVZg83FWMMUChVLA',
            'hash' => 'e53e0fdb50b768b9db228138c8a4feaa',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pca.reports', [
            'play_id' => 'dzrw9r8nvu027',
            'currency' => 'IDR',
            'game_code' => 'rol;rol_autoroulette2',
            'bet_choice' => '-',
            'bet_time' => '2021-01-01 00:00:30',
            'bet_id' => '80087673-4905-29b8-04f3-c9401dcda78c',
            'wager_amount' => 0,
            'payout_amount' => 0,
            'status' => 'PAYOUT',
            'ref_id' => 'ed6fa4c8-e023-b697-4f6e-c0b79333a242'
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_playerNotFound_expectedData()
    {
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'invalidPlayID',
            'bet' => '0',
            'win' => '100',
            'jackpot_win' => '0',
            'game_name' => 'test;testGameID',
            'free_spin' =>
                [
                    'win' => 0,
                    'played' => 0,
                    'remained' => 0
                ],
            'transaction_id' => 'testTransactionID',
            'round_id' => 'testRoundID',
            'session_id' => 'testSessionID',
            'token' => 'testToken',
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
            'hash' => '446837782664787c4d26f064c736c785',
        ]);

        $response->assertStatus(404);
    }

    public function test_betAndSettle_invalidHash_expectedData()
    {
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '0',
            'win' => '100',
            'jackpot_win' => '0',
            'game_name' => 'test;testGameID',
            'free_spin' =>
                [
                    'win' => 0,
                    'played' => 0,
                    'remained' => 0
                ],
            'transaction_id' => 'testTransactionID',
            'round_id' => 'testRoundID',
            'session_id' => 'testSessionID',
            'token' => 'testToken',
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

    public function test_betAndSettle_refIDAlreadyExist_expectedData()
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
                            return [
                                'credit_after' => 1000.0
                            ];
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
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '100',
            'win' => '0',
            'jackpot_win' => '0',
            'game_name' => 'test;testGameID',
            'free_spin' =>
                [
                    'win' => 0,
                    'played' => 0,
                    'remained' => 0
                ],
            'transaction_id' => 'testTransactionID',
            'round_id' => 'testRoundID',
            'session_id' => 'testSessionID',
            'token' => 'testToken',
            'request_token' => 'testRequestToken'
        ];

        Carbon::setTestNow('2021-01-02 00:00:00');

        $hash = $this->createHash($payload);

        $payload['hash'] = $hash;

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'token' => 'testToken',
            'hash' => '4a02f72cddb37bfee9b2713818540868',
            'reason' => ''
        ]);

        $this->assertDatabaseMissing('pca.reports', [
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-02 00:00:00',
            'bet_id' => 'testRoundID',
            'wager_amount' => 100,
            'payout_amount' => 0,
            'status' => 'PAYOUT',
            'ref_id' => 'testTransactionID'
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_insufficientFund_expectedData()
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

        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '10000',
            'win' => '0',
            'jackpot_win' => '0',
            'game_name' => 'test;testGameID',
            'free_spin' =>
                [
                    'win' => 0,
                    'played' => 0,
                    'remained' => 0
                ],
            'transaction_id' => 'testTransactionID',
            'round_id' => 'testRoundID',
            'session_id' => 'testSessionID',
            'token' => 'testToken',
            'request_token' => 'testRequestToken'
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 1000.00,
            'token' => 'testToken',
            'hash' => '3a8a6f5a1e42ba4599456cb346c25085',
            'reason' => 'Member Insufficient Balance'
        ]);

        $response->assertStatus(400);
    }

    public function test_betAndSettle_emptyWallet_expectedData()
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
                            return null;
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

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '100',
            'win' => '0',
            'jackpot_win' => '0',
            'game_name' => 'test;testGameID',
            'free_spin' =>
                [
                    'win' => 0,
                    'played' => 0,
                    'remained' => 0
                ],
            'transaction_id' => 'testTransactionID',
            'round_id' => 'testRoundID',
            'session_id' => 'testSessionID',
            'token' => 'testToken',
            'request_token' => 'testRequestToken'
        ];

        Carbon::setTestNow('2021-01-01 00:00:00');

        $hash = $this->createHash($payload);

        $payload['hash'] = $hash;

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 1000.00,
            'token' => 'testToken',
            'reason' => 'Internal Server Error',
            'hash' => 'ca0392cba87ca6de6593208157cccb6c',
        ]);

        $response->assertStatus(500);

        Carbon::setTestNow();
    }

    /**
     * @dataProvider betAndSettleParams
     */
    public function test_betAndSettle_invalidRequest_expectedData($params, $hash)
    {
        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '100',
            'win' => '0',
            'game_name' => 'test;testGameID',
            'transaction_id' => 'testTransactionID',
            'round_id' => 'testRoundID',
            'token' => 'testToken',
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
            'hash' => $hash
        ]);

        $response->assertStatus(500);
    }

    public static function betAndSettleParams()
    {
        return [
            ['user_id', '8bda482b31b912336fde03e6c6487fcf'],
            ['bet', '8bda482b31b912336fde03e6c6487fcf'],
            ['win', '8bda482b31b912336fde03e6c6487fcf'],
            ['game_name', '8bda482b31b912336fde03e6c6487fcf'],
            ['round_id','8bda482b31b912336fde03e6c6487fcf'],
            ['token', '63fbaf92c987cc40a7683092c62aaecf'],
            ['hash', '8bda482b31b912336fde03e6c6487fcf'],
        ];
    }
}
