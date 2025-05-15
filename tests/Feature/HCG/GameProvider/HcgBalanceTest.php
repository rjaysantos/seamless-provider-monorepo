<?php

use Tests\TestCase;
use App\Models\HcgPlayer;
use App\Contracts\IWallet;
use Illuminate\Support\Carbon;
use App\Contracts\IWalletFactory;
use App\GameProviders\Hcg\HcgCredentials;
use App\GameProviders\Hcg\HcgEncryption;
use Illuminate\Support\Facades\DB;

class HcgBalanceTest extends TestCase
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

    public function test_balance_validRequest_expectedData()
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

        HcgPlayer::factory()->create([
            'play_id' => 'playId',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 1,
            'uid' => 'playId',
        ];

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            "code" => 0,
            "gold" => 1
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider oneToOneCurrencies
     */
    public function test_balance_validRequestMultipleOneToOneCurrencies_expectedData($currency)
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

        HcgPlayer::factory()->create([
            'play_id' => 'playId',
            'currency' => $currency
        ]);

        $payload = [
            'action' => 1,
            'uid' => 'playId',
        ];

        $signature = $this->createSignature($payload, $currency);

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/' . $currency, $payload);

        $response->assertJson([
            "code" => 0,
            "gold" => 1000
        ]);

        $response->assertStatus(200);
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
    public function test_balance_validRequestMultipleOneToOneThousandCurrencies_expectedData($currency)
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

        HcgPlayer::factory()->create([
            'play_id' => 'playId',
            'currency' => $currency
        ]);

        $payload = [
            'action' => 1,
            'uid' => 'playId',
        ];

        $signature = $this->createSignature($payload, $currency);

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/' . $currency, $payload);

        $response->assertJson([
            "code" => 0,
            "gold" => 1
        ]);

        $response->assertStatus(200);
    }

    public static function oneToOneThousandCurrencies()
    {
        return [
            ['IDR'],
            ['VND']
        ];
    }

    /**
     * @dataProvider balanceParams
     */
    public function test_balance_invalidRequest_expectedData($unset)
    {
        $payload = [
            'action' => 1,
            'uid' => 'playId',
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

    public static function balanceParams()
    {
        return [
            ['action'],
            ['uid'],
            ['sign']
        ];
    }

    public function test_balance_invalidSignature_expectedData()
    {
        $payload = [
            'action' => 1,
            'uid' => 'playId',
        ];

        $payload['sign'] = 'invalid signature';

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '207',
            'message' => 'Sign error'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_invalidAction_expectedData()
    {
        $payload = [
            'action' => 99,
            'uid' => 'playId',
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

    public function test_balance_playerNotFound_expectedData()
    {
        $payload = [
            'action' => 1,
            'uid' => 'playId',
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

    public function test_balance_emptyWallet_expectedData()
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
                            return null;
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

        HcgPlayer::factory()->create([
            'play_id' => 'playId',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 1,
            'uid' => 'playId',
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
