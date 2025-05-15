<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\Wallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class BesGetBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE bes.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_getBalance_validData_balanceResponse()
    {
        DB::table('bes.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
            ]);

        $request = [
            'action' => 1,
            'uid' => 'test-player-1',
            'currency' => 'IDR'
        ];

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

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'action' => 1,
            'status' => 1,
            'balance' => 1000.0,
            'currency' => 'IDR'
        ]);

        $response->assertStatus(200);
    }


    #[DataProvider('playParams')]
    public function test_play_incompleteRequestParameters_invalidRequestResponse($param)
    {
        $request = [
            'action' => 1,
            'uid' => 'test-player-1',
            'currency' => 'IDR'
        ];

        unset($request[$param]);

        app()->bind(IWallet::class, TestWallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'status' => 1008
        ]);

        $response->assertStatus(200);
    }

    public static function playParams()
    {
        return [
            ['action'],
            ['uid'],
            ['currency']
        ];
    }

    public function test_getBalance_invalidUID_playerNotFoundResponse()
    {

        $request = [
            'action' => 1,
            'uid' => 'invalid-player-1',
            'currency' => 'IDR'
        ];

        app()->bind(IWallet::class, TestWallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'status' => 1004
        ]);

        $response->assertStatus(200);
    }

    public function test_play_walletError_walletErrorResponse()
    {
        DB::table('bes.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'test-player-username',
                'currency' => 'IDR',
            ]);

        $request = [
            'action' => 1,
            'uid' => 'test-player-1',
            'currency' => 'IDR'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        $response = $this->post('bes/prov', $request);

        $response->assertJson([
            'status' => 1014
        ]);

        $response->assertStatus(200);
    }
}
