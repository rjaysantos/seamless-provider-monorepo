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

class PlaBetAndSettleTest extends TestCase
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '100',
            'win' => '0',
            'jackpot_win' => '0',
            'game_name' => 'testGameID',
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 900.00,
            'token' => 'testToken',
            'hash' => '2b7adfff1f6da1e50baed0dd93475aac',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pla.reports', [
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
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
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '200',
            'win' => '0',
            'jackpot_win' => '0',
            'game_name' => 'testGameID',
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 700.00,
            'token' => 'testToken',
            'hash' => 'c035c6f481730b263ae440b64446c988',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pla.reports', [
            'trx_id' => 'testRoundID',
            'bet_amount' => 200,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:10',
            'updated_at' => '2021-01-01 00:00:10',
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2020-12-31 16:00:00',
            'ref_id' => 'testTransactionID1'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:10',
            'updated_at' => '2020-12-31 16:00:10',
            'ref_id' => 'testTransactionID2'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '0',
            'win' => '200',
            'jackpot_win' => '0',
            'game_name' => 'testGameID',
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'token' => 'testToken',
            'hash' => 'be93407079cb42e95ed53772f87e4594',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pla.reports', [
            'trx_id' => 'testRoundID',
            'bet_amount' => 0,
            'win_amount' => 200,
            'created_at' => '2021-01-01 00:00:20',
            'updated_at' => '2021-01-01 00:00:20',
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2020-12-31 16:00:00',
            'ref_id' => 'testTransactionID1'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:10',
            'updated_at' => '2020-12-31 16:00:10',
            'ref_id' => 'testTransactionID2'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 0,
            'win_amount' => 200,
            'created_at' => '2021-01-01 00:00:20',
            'updated_at' => '2022-06-25 05:59:08',
            'ref_id' => 'testTransactionID3'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '0',
            'win' => '100',
            'jackpot_win' => '0',
            'game_name' => 'testGameID',
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1100.00,
            'token' => 'testToken',
            'hash' => 'f760230471950ef95551e35456d8671c',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pla.reports', [
            'trx_id' => 'testRoundID',
            'bet_amount' => 0,
            'win_amount' => 100,
            'created_at' => '2021-01-01 00:00:30',
            'updated_at' => '2021-01-01 00:00:30',
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

        PlaPlayer::factory()->create([
            'play_id' => 'dzrw9r8nvu027',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => '80087673-4905-29b8-04f3-c9401dcda78c',
            'bet_amount' => 100,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2020-12-31 16:00:00',
            'ref_id' => 'ed6fa4c8-e023-b697-4f6e-c0b79333a424'
        ]);

        $payload = [
            "action" => "update_balance",
            "user_id" => "dzrw9r8nvu027",
            "bet" => "0",
            "win" => "0",
            "jackpot_win" => "0",
            "game_name" => "bfb",
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'token' => '8dbwE5NWyrHahQZqWVZg83FWMMUChVLA',
            'hash' => 'dc603921187ad76046ec703b1fae58ff',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pla.reports', [
            'trx_id' => '80087673-4905-29b8-04f3-c9401dcda78c',
            'bet_amount' => 0,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:30',
            'updated_at' => '2021-01-01 00:00:30',
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

        PlaPlayer::factory()->create([
            'play_id' => 'dzrw9r8nvu027',
            'currency' => 'IDR'
        ]);

        $payload = [
            "action" => "update_balance",
            "user_id" => "dzrw9r8nvu027",
            "bet" => "0",
            "win" => "0",
            "jackpot_win" => "0",
            "game_name" => "bfb",
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'token' => '8dbwE5NWyrHahQZqWVZg83FWMMUChVLA',
            'hash' => 'dc603921187ad76046ec703b1fae58ff',
            'reason' => ''
        ]);

        $this->assertDatabaseHas('pla.reports', [
            'trx_id' => '80087673-4905-29b8-04f3-c9401dcda78c',
            'bet_amount' => 0,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:30',
            'updated_at' => '2021-01-01 00:00:30',
            'ref_id' => 'ed6fa4c8-e023-b697-4f6e-c0b79333a242'
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_betAndSettle_playerNotFound_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'invalidPlayID',
            'bet' => '0',
            'win' => '100',
            'jackpot_win' => '0',
            'game_name' => 'testGameID',
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => 'testToken',
            'reason' => 'Member Not Exists',
            'hash' => 'fcea29b9fa318058cf4a2265e795b935',
        ]);

        $response->assertStatus(404);
    }

    public function test_betAndSettle_invalidHash_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '0',
            'win' => '100',
            'jackpot_win' => '0',
            'game_name' => 'testGameID',
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

        PlaPlayer::factory()->create([
            'play_id' => 'dzrw9r8nvu027',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => '2b72b0f4-4c9b-0b5d-c788-f1e691b5c12e',
            'bet_amount' => 1,
            'win_amount' => 0,
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
            'ref_id' => '31369783-7edd-be5e-4f58-2c2ad98bd05e'
        ]);

        $payload = [
            "action" => "update_balance",
            "user_id" => "dzrw9r8nvu027",
            "bet" => "1.00",
            "win" => "0.00",
            "jackpot_win" => "0.00",
            "game_name" => "bfb",
            "free_spin" => [
                "win" => 0,
                "played" => 0,
                "remained" => 0
            ],
            "transaction_id" => "31369783-7edd-be5e-4f58-2c2ad98bd05e",
            "session_id" => "f22f5213-4f9c-1bea-b133-e3969c16228d",
            "round_id" => "2b72b0f4-4c9b-0b5d-c788-f1e691b5c12e",
            "token" => "IF7FG1eEUDgoZxddrmFntwvmrlsDLAcB",
            "request_token" => "34e44589-7b73-40a7-a42c-63697000c647",
            "hash" => "8ade8fb82bab97dfc6c368a7c33346c1"
        ];

        Carbon::setTestNow('2021-01-02 00:00:00');

        $hash = $this->createHash($payload);

        $payload['hash'] = $hash;

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'token' => 'IF7FG1eEUDgoZxddrmFntwvmrlsDLAcB',
            'hash' => 'cd8af8998dda2b6c3725a4454042f467',
            'reason' => ''
        ]);

        $this->assertDatabaseMissing('pla.reports', [
            'trx_id' => '2b72b0f4-4c9b-0b5d-c788-f1e691b5c12e',
            'bet_amount' => 1,
            'win_amount' => 0,
            'created_at' => '2021-01-02 00:00:00',
            'updated_at' => '2021-01-02 00:00:00',
            'ref_id' => '31369783-7edd-be5e-4f58-2c2ad98bd05e'
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '10000',
            'win' => '0',
            'jackpot_win' => '0',
            'game_name' => 'testGameID',
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 1000.00,
            'token' => 'testToken',
            'hash' => '888dc9a7a196a2180e87bb1efb370ae3',
            'reason' => 'Member Insufficient Balance',
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'update_balance',
            'user_id' => 'testPlayID',
            'bet' => '100',
            'win' => '0',
            'jackpot_win' => '0',
            'game_name' => 'testGameID',
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

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 1000.00,
            'reason' => 'Internal Server Error',
            'balance' => 1000,
            'hash' => '240b3e84ae99f758f6960c519d2162a9',
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
            'game_name' => 'testGameID',
            'transaction_id' => 'testTransactionID',
            'round_id' => 'testRoundID',
            'token' => 'testToken',
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

    public static function betAndSettleParams()
    {
        return [
            ['user_id', '5e7ef8133742e452cf5070700e9930bb'],
            ['bet', '5e7ef8133742e452cf5070700e9930bb'],
            ['win', '5e7ef8133742e452cf5070700e9930bb'],
            ['game_name', '5e7ef8133742e452cf5070700e9930bb'],
            ['round_id', '5e7ef8133742e452cf5070700e9930bb'],
            ['token', '57e3f51a79ec2152711684c60e6bfeec'],
            ['hash', '5e7ef8133742e452cf5070700e9930bb'],
        ];
    }
}
