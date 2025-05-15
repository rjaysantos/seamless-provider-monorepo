<?php

use Tests\TestCase;
use App\Models\SboPlayer;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;

class SboBalanceTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE sbo.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sbo.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_getBalance_validRequest_expectedData()
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
                                'credit' => 100.0,
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

        SboPlayer::factory()->create([
            'play_id' => 'player_id',
            'currency' => 'IDR'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'player_id'
        ];

        $response = $this->post('/sbo/prov/GetBalance', $request);

        $response->assertJson([
            'AccountName' => 'player_id',
            'Balance' => 100.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider productionParams
     */
    public function test_getBalance_prodValidRequestMultipleCurrency_expectedData($currency, $companyKey)
    {
        config(['app.env' => 'PRODUCTION']);

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
                                'credit' => 100.0,
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

        SboPlayer::factory()->create([
            'play_id' => 'player_id',
            'currency' => $currency
        ]);

        $request = [
            'CompanyKey' => $companyKey,
            'Username' => 'player_id'
        ];

        $response = $this->post('/sbo/prov/GetBalance', $request);

        $response->assertJson([
            'AccountName' => 'player_id',
            'Balance' => 100.0,
            'ErrorCode' => 0,
            'ErrorMessage' => 'No Error'
        ]);

        $response->assertStatus(200);
    }

    public static function productionParams()
    {
        return [
            ['IDR', '7DC996ABC2E642339147E5F776A3AE85'],
            ['THB', '7DC996ABC2E642339147E5F776A3AE85'],
            ['VND', '7DC996ABC2E642339147E5F776A3AE85'],
            ['BRL', '7DC996ABC2E642339147E5F776A3AE85'],
            ['USD', '7DC996ABC2E642339147E5F776A3AE85'],
        ];
    }

    public function test_getBalance_invalidCompanyKey_expectedData()
    {
        SboPlayer::factory()->create([
            'play_id' => 'player_id'
        ]);

        $request = [
            'CompanyKey' => 'invalid_company_key',
            'Username' => 'player_id'
        ];

        $response = $this->post('/sbo/prov/GetBalance', $request);

        $response->assertJson([
            'ErrorCode' => 4,
            'ErrorMessage' => 'CompanyKey Error'
        ]);

        $response->assertStatus(200);
    }

    public function test_getBalance_playerNotFound_expectedData()
    {
        SboPlayer::factory()->create([
            'play_id' => 'player_id',
            'currency' => 'IDR'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'non_existent_player'
        ];

        $response = $this->post('/sbo/prov/GetBalance', $request);

        $response->assertJson([
            'ErrorCode' => 1,
            'ErrorMessage' => 'Member not exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_getBalance_emptyWalletResponse_expected()
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

        SboPlayer::factory()->create([
            'play_id' => 'player_id',
            'currency' => 'IDR'
        ]);

        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'player_id'
        ];

        $response = $this->post('/sbo/prov/GetBalance', $request);

        $response->assertJson([
            'ErrorCode' => 7,
            'ErrorMessage' => 'Internal Error'
        ]);

        $response->assertStatus(200);
    }

    /**
     * @dataProvider balanceParams
     */
    public function test_getBalance_incompleteRequest_expectedData($param)
    {
        $request = [
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'non_existent_player'
        ];

        unset($request[$param]);

        $response = $this->post('/sbo/prov/GetBalance', $request);

        $response->assertJson([
            'ErrorCode' => 3,
            'ErrorMessage' => 'Username empty'
        ]);

        $response->assertStatus(200);
    }

    public static function balanceParams()
    {
        return [
            ['CompanyKey'],
            ['Username']
        ];
    }
}
