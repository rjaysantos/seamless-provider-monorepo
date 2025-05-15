<?php

use Tests\TestCase;
use App\Models\PlaPlayer;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use App\GameProviders\Pla\PlaEncryption;
use App\GameProviders\Pla\PlaCredentials;

class PlaBalanceTest extends TestCase
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

    public function test_balance_validRequest_expectedData()
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
            'action' => 'request_balance',
            'user_id' => 'testPlayID',
            'token' => 'testToken'
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'user_id' => 'testPlayID',
            'token' => 'testToken',
            'hash' => '7fa0a7cb2327cfa8db79a1c907aebd0a'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_playerNotFound_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'request_balance',
            'user_id' => 'invalidPlayID',
            'token' => 'testToken'
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

    public function test_balance_invalidHash_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'request_balance',
            'user_id' => 'testPlayID',
            'token' => 'testToken'
        ];

        $payload['hash'] = 'invalidHash';

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => 'testToken',
            'reason' => 'Invalid Signature',
            'hash' => 'e6927cb51997735916d8ab8bcf4adec1',
        ]);

        $response->assertStatus(401);
    }

    public function test_balance_invalidWalletResponse_expectedData()
    {
        app()->bind(IWalletFactory::class, function () {
            return new class implements IWalletFactory {
                public function makeWallet($credentials): IWallet
                {
                    return new class implements IWallet {
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

        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'request_balance',
            'user_id' => 'testPlayID',
            'token' => 'testToken'
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pla/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => 'testToken',
            'reason' => 'Internal Server Error',
            'hash' => '2fac1f1292cfcf7bc70432bf917f1b6c',
        ]);

        $response->assertStatus(500);
    }

    /**
     * @dataProvider balanceParams
     */
    public function test_balance_invalidRequest_expectedData($unset, $hash)
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'request_balance',
            'user_id' => 'testPlayID',
            'token' => 'testToken',
        ];

        if ($unset != 'hash') {
            unset($payload[$unset]);
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

    public static function balanceParams()
    {
        return [
            ['action', '5e7ef8133742e452cf5070700e9930bb'],
            ['user_id', '5e7ef8133742e452cf5070700e9930bb'],
            ['token', '57e3f51a79ec2152711684c60e6bfeec'],
            ['hash', '5e7ef8133742e452cf5070700e9930bb'],
        ];
    }
}
