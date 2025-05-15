<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;

class OrsBalanceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ors.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_getBalance_validRequest_expected()
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

    /**
     * @dataProvider currencyParameters
     */
    public function test_getBalance_prodValidRequestMultipleCurrency_expected($currency, $signature, $key)
    {
        config(['app.env' => 'PRODUCTION']);

        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => $currency,
        ]);

        $request = [
            'player_id' => 'player_id',
            'timestamp' => 1715052653,
            'signature' => $signature
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
            'key' => $key
        ]);

        $response->assertJson([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_id' => 'player_id',
            'player_status' => 'activate',
            'balance' => 100.0,
            'timestamp' => Carbon::parse('2020-01-01 00:00:00')->timestamp,
            'currency' => $currency
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public static function currencyParameters()
    {
        return [
            // ['IDR', '4b84d9c5df579703b67795108a823240', 'vPJmbdRMpvNeJ26RC4khwvQ7hBAgwxYJ'],
            ['PHP', '1fa790f34efaf7f4f97620d1297be6ae', '4NUH3zFeOXmhe5PACHTe7uV92vYStthj'],
            ['THB', '3858a7db7e67d1d57ace236549d725fb', 'YXFdYTmY8wMa4FtxuQ4EqS0QQLc0vHNS'],
            ['VND', 'eb31a211cce6f2c9caadb80689fd7c1e', 'L3hEACJsTTLn8LSXogwkr3CDDt0LGmVG'],
            ['BRL', 'da40f7a862a6d5c803fe70a713dd411e', 'vAaAYEWbtHEdshR5fZGK4lDpYHGCI2DE'],
            ['USD', 'ed3fc05769e2d2c62984a9a87d988baa', 'BPCOai2ys6l85Gt7EAK3bw1AeJruzDyZ'],
        ];
    }

    public function test_getBalance_invalidSignature_expected()
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

    public function test_getBalance_invalidPublicKeyHeader_expected()
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

    public function test_getBalance_playNotFound_expected()
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

    public function test_getBalance_emptyWalletResponse_expected()
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

    public function test_getBalance_incompleteRequest_expected()
    {
        $request = [
            'timestamp' => 1715052653,
        ];

        $response = $this->get('/ors/prov/api/v2/operator/player/balance?' . http_build_query($request), [
            'key' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x'
        ]);

        $response->assertJson([
            'rs_code' => 'E-104',
            'rs_message' => 'invalid parameter',
        ]);

        $response->assertStatus(200);
    }
}
