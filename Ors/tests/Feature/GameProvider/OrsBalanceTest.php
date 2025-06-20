<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class OrsBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ors.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_balance_validRequest_expected()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $request = [
            'player_id' => 'player_id',
            'timestamp' => 1715052653,
            'signature' => '2822f449dca57f03bc6cc990d450c8ed'
        ];

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 100.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        Carbon::setTestNow('2020-01-01 00:00:00');

        $response = $this->get('/ors/prov/api/v2/operator/player/balance?' . http_build_query($request), [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_id' => 'player_id',
            'player_status' => 'activate',
            'balance' => 100.0,
            'timestamp' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
            'currency' => 'IDR'
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    #[DataProvider('balanceParameters')]
    public function test_balance_incompleteParameter_expected($param)
    {
        $request = [
            'player_id' => 'player_id',
            'timestamp' => 1715052653,
            'signature' => '2822f449dca57f03bc6cc990d450c8ed'
        ];

        unset($request[$param]);

        $response = $this->get('/ors/prov/api/v2/operator/player/balance?' . http_build_query($request), [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'E-104',
            'rs_message' => 'invalid parameter',
        ]);

        $response->assertStatus(200);
    }

    public static function balanceParameters()
    {
        return [
            ['player_id'],
            ['signature'],
        ];
    }

    public function test_balance_invalidSignature_expected()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $request = [
            'player_id' => 'player_id',
            'timestamp' => 1715052653,
            'signature' => 'Invalid Signature'
        ];

        $response = $this->get('/ors/prov/api/v2/operator/player/balance?' . http_build_query($request), [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'E-103',
            'rs_message' => 'invalid signature',
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_invalidPublicKeyHeader_expected()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $request = '{
            "player_id": "player_id",
            "timestamp": 1715052653,
            "signature": "2822f449dca57f03bc6cc990d450c8ed"
        }';

        $response = $this->call(
            'GET',
            '/ors/prov/api/v2/operator/player/balance?' . http_build_query(json_decode($request, true)),
            [],
            [],
            [],
            [
                'HTTP_KEY' => 'Invalid Key',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'E-102',
            'rs_message' => 'invalid public key in header',
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_playerNotFound_expected()
    {
        $request = [
            'player_id' => 'test_player',
            'timestamp' => 1715052653,
            'signature' => 'e13e558779728a2b1dc043ffb073bc9d',
        ];

        $response = $this->get('/ors/prov/api/v2/operator/player/balance?' . http_build_query($request), [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'S-104',
            'rs_message' => 'player not available',
        ]);

        $response->assertStatus(200);
    }

    public function test_balance_invalidWalletResponse_expected()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $request = [
            'player_id' => 'player_id',
            'timestamp' => 1715052653,
            'signature' => '2822f449dca57f03bc6cc990d450c8ed'
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

        $response = $this->get('/ors/prov/api/v2/operator/player/balance?' . http_build_query($request), [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'S-113',
            'rs_message' => 'internal error on the operator',
        ]);

        $response->assertStatus(200);
    }
}
