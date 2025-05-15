<?php

use Tests\TestCase;
use App\Models\HcgPlayer;
use App\Models\HcgReport;
use App\Contracts\IWallet;
use Illuminate\Support\Carbon;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use App\Factories\TestWalletFactory;
use Illuminate\Support\Facades\Http;
use App\GameProviders\Hcg\HcgEncryption;
use App\GameProviders\Hcg\HcgCredentials;

class HcgBetAndSettleTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE hcg.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hcg.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hcg.playgame RESTART IDENTITY;');
        parent::tearDown();
    }

    public function createSignature($payload, $currency)
    {
        $credentialsLib = new HcgCredentials();
        $credentials = $credentialsLib->getCredentialsByCurrency($currency);
        $encryptionLib = new HcgEncryption($credentials);

        return $encryptionLib->createSignature($payload);
    }

    public function test_betAndSettle_validRequest_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory
            {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet
                    {
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
                                'credit_after' => 3000
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

        HcgPlayer::factory()->create([
            'play_id' => 'playID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ];

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            "code" => 0,
            "gold" => 3
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hcg.reports', [
            'trx_id' => '0-transactionID',
            'bet_amount' => 1000,
            'win_amount' => 3000,
            'created_at' => '2024-08-14 14:47:42',
            'updated_at' => '2024-08-14 14:47:42'
        ]);
    }

    /**
     * @dataProvider oneToOneCurrencies
     */
    public function test_cancelBetAndSettle_validRequestOneToOneCurrencies_expectedData($currency)
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory
            {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet
                    {
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
                                'credit_after' => 3000
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

        HcgPlayer::factory()->create([
            'play_id' => 'playID',
            'currency' => $currency
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 1000,
            'win' => 3000,
        ];

        $signature = $this->createSignature($payload, $currency);

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/' . $currency, $payload);

        $response->assertJson([
            "code" => 0,
            "gold" => 3000
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hcg.reports', [
            'trx_id' => '0-transactionID',
            'bet_amount' => 1000,
            'win_amount' => 3000,
            'created_at' => '2024-08-14 14:47:42',
            'updated_at' => '2024-08-14 14:47:42'
        ]);
    }

    public static function oneToOneCurrencies()
    {
        return [
            ['USD'],
            ['THB'],
            ['BRL'],
            ['PHP']
        ];
    }

    /**
     * @dataProvider oneToOneThousandCurrencies
     */
    public function test_cancelBetAndSettle_validRequestOneToOneThousandCurrencies_expectedData($currency)
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory
            {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet
                    {
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
                                'credit_after' => 3000
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

        HcgPlayer::factory()->create([
            'play_id' => 'playID',
            'currency' => $currency
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ];

        $signature = $this->createSignature($payload, $currency);

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/' . $currency, $payload);

        $response->assertJson([
            "code" => 0,
            "gold" => 3
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hcg.reports', [
            'trx_id' => '0-transactionID',
            'bet_amount' => 1000,
            'win_amount' => 3000,
            'created_at' => '2024-08-14 14:47:42',
            'updated_at' => '2024-08-14 14:47:42'
        ]);
    }

    public static function oneToOneThousandCurrencies()
    {
        return [
            ['IDR'],
            ['VND']
        ];
    }

    /**
     * @dataProvider betAndSettleParams
     */
    public function test_betAndSettle_invalidRequest_expectedData($unset)
    {
        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => (int)Carbon::now()->valueOf(),
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        unset($payload[$unset]);

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        if ($unset == 'sign')
            unset($payload[$unset]);

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Validation error'
        ]);

        $response->assertStatus(200);
    }

    public static function betAndSettleParams()
    {
        return [
            ['action'],
            ['uid'],
            ['timestamp'],
            ['orderNo'],
            ['gameCode'],
            ['bet'],
            ['win'],
            ['sign']
        ];
    }

    public function test_betAndSettle_invalidSignature_expectedData()
    {
        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => (int)Carbon::now()->valueOf(),
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $payload['sign'] = 'invalid Signature';

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '207',
            'message' => 'Sign error'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_invalidAction_expectedData()
    {
        $payload = [
            'action' => 999,
            'uid' => 'playID',
            'timestamp' => (int)Carbon::now()->valueOf(),
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Action parameter error'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_playerNotFound_expectedData()
    {
        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => (int)Carbon::now()->valueOf(),
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '101',
            'message' => 'User not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_transactionAlreadyExist_expectedData()
    {
        HcgPlayer::factory()->create([
            'play_id' => 'playID',
            'currency' => 'IDR'
        ]);

        HcgReport::factory()->create([
            'trx_id' => '0-transactionID'
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => (int)Carbon::now()->valueOf(),
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '102',
            'message' => 'Duplicate order number'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_insufficientFund_expectedData()
    {
        app()->bind(IWalletFactory::class, TestWalletFactory::class);

        HcgPlayer::factory()->create([
            'play_id' => 'playID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => (int)Carbon::now()->valueOf(),
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 2,
            'win' => 3,
        ];

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '106',
            'message' => 'Balance is not enough'
        ]);

        $response->assertStatus(200);
    }

    public function test_betAndSettle_emptyWallet_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory
            {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet
                    {
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

        HcgPlayer::factory()->create([
            'play_id' => 'playID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 2,
            'uid' => 'playID',
            'timestamp' => 1723618062,
            'orderNo' => "transactionID",
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ];

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Wallet error'
        ]);

        $response->assertStatus(200);
    }
}
