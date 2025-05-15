<?php

use Tests\TestCase;
use App\Models\PcaPlayer;
use App\Contracts\IWallet;
use App\Contracts\IWalletFactory;
use Illuminate\Support\Facades\DB;
use App\GameProviders\Pla\PlaEncryption;
use App\GameProviders\Pca\PcaCredentials;

class PcaBalanceTest extends TestCase
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

        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'request_balance',
            'user_id' => 'testPlayID',
            'token' => 'testToken'
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => true,
            'balance' => 1000.00,
            'user_id' => 'testPlayID',
            'token' => 'testToken',
            'hash' => '67ca81879a7d4bfc457447e5017d3d1f'
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_playerNotFound_expectedData()
    {
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'request_balance',
            'user_id' => 'invalidPlayID',
            'token' => 'testToken'
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

    public function test_balance_invalidHash_expectedData()
    {
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'request_balance',
            'user_id' => 'testPlayID',
            'token' => 'testToken'
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

        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $payload = [
            'action' => 'request_balance',
            'user_id' => 'testPlayID',
            'token' => 'testToken'
        ];

        $payload['hash'] = $this->createHash($payload);

        $response = $this->post('pca/prov', $payload);

        $response->assertJson([
            'success' => false,
            'balance' => 0,
            'token' => 'testToken',
            'reason' => 'Internal Server Error',
            'hash' => '1535937f1487018184a2e18ddf4d20b5'
        ]);

        $response->assertStatus(500);
    }

    /**
     * @dataProvider balanceParams
     */
    public function test_balance_invalidRequest_expectedData($unset, $hash)
    {
        PcaPlayer::factory()->create([
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

    public static function balanceParams()
    {
        return [
            ['action', '8bda482b31b912336fde03e6c6487fcf'],
            ['user_id', '8bda482b31b912336fde03e6c6487fcf'],
            ['token', '63fbaf92c987cc40a7683092c62aaecf'],
            ['hash', '8bda482b31b912336fde03e6c6487fcf'],
        ];
    }
}
