<?php

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

class OrsAuthenticateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ors.players RESTART IDENTITY;');
    }

    public function test_authenticate_validRequest_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'token' => 'test_token',
        ]);

        $request = '{
            "player_id":"player_id",
            "timestamp":1715052653,
            "token":"test_token",
            "signature":"e566dd85432703d401bdd619751df042"
        }';

        $response = $this->call(
            'POST',
            'ors/prov/api/v2/operator/security/authenticate',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_status' => 'activate',
            'token' => 'test_token'
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('currencyParameters')]
    public function test_authenticate_prodValidRequestMultipleCurrency_expectedData($currency, $signature, $key)
    {
        config(['app.env' => 'PRODUCTION']);

        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => $currency,
            'token' => 'test_token',
        ]);

        $request = '{
            "player_id":"player_id",
            "timestamp":1715052653,
            "token":"test_token",
            "signature":"' . $signature . '"
        }';

        $response = $this->call(
            'POST',
            'ors/prov/api/v2/operator/security/authenticate',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => $key,
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'S-100',
            'rs_message' => 'success',
            'player_status' => 'activate',
            'token' => 'test_token'
        ]);

        $response->assertStatus(200);
    }

    public static function currencyParameters()
    {
        return [
            ['IDR', '24be92ae1b4052aabf88922c60126f6e', 'vPJmbdRMpvNeJ26RC4khwvQ7hBAgwxYJ'],
            ['PHP', 'e3e0072acf8d1e68d3a398e51dc3eb8d', '4NUH3zFeOXmhe5PACHTe7uV92vYStthj'],
            ['THB', '703055240b6fa81e3f8e7e0fdff94851', 'YXFdYTmY8wMa4FtxuQ4EqS0QQLc0vHNS'],
            ['VND', '5bb3efc10a4b73112810c1a3fbbeb939', 'L3hEACJsTTLn8LSXogwkr3CDDt0LGmVG'],
            ['BRL', '861d0e78a05a690206b40997a06e38c3', 'vAaAYEWbtHEdshR5fZGK4lDpYHGCI2DE'],
            ['USD', 'e4b80af263102693923a60c4c0832949', 'BPCOai2ys6l85Gt7EAK3bw1AeJruzDyZ'],
        ];
    }

    public function test_authenticate_invalidPublicKeyHeader_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "player_id":"player_id",
            "timestamp":1715052653,
            "token":"test_token",
            "signature":"e566dd85432703d401bdd619751df042"
        }';

        $response = $this->call(
            'POST',
            'ors/prov/api/v2/operator/security/authenticate',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'invalid key header',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'E-102',
            'rs_message' => 'invalid public key in header',
        ]);

        $response->assertStatus(200);
    }

    public function test_authenticate_invalidSignature_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "player_id":"player_id",
            "timestamp":1715052653,
            "token":"test_token",
            "signature":"invalid_signature"
        }';

        $response = $this->call(
            'POST',
            'ors/prov/api/v2/operator/security/authenticate',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'E-103',
            'rs_message' => 'invalid signature',
        ]);

        $response->assertStatus(200);
    }

    public function test_authenticate_invalidToken_expectedData()
    {
        DB::table('ors.players')->insert([
            'play_id' => 'player_id',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        $request = '{
            "player_id" : "player_id",
            "timestamp" : 1715052653,
            "token" : "invalid_token",
            "signature":"7fa4eecf50a7970e2252df90bb4fffa0"
        }';

        $response = $this->call(
            'POST',
            'ors/prov/api/v2/operator/security/authenticate',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'E-106',
            'rs_message' => 'token is invalid',
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_invalidRequest_expectedData($request)
    {
        $response = $this->call(
            'POST',
            'ors/prov/api/v2/operator/security/authenticate',
            json_decode($request, true),
            [],
            [],
            [
                'HTTP_KEY' => 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x',
            ],
            $request
        );

        $response->assertJson([
            'rs_code' => 'E-104',
            'rs_message' => 'invalid parameter',
        ]);

        $response->assertStatus(200);
    }

    public static function authenticateParams()
    {
        return [
            [
                '{
                    "timestamp":1715052653,
                    "token":"test_token",
                    "signature":"f8e5ad08bb7a4e24a906dff6ec5f738e"
                }'
            ],
            [
                '{
                    "player_id":"player_id",
                    "timestamp":1715052653,
                    "signature":"2822f449dca57f03bc6cc990d450c8ed"
                }'
            ],
        ];
    }
}
