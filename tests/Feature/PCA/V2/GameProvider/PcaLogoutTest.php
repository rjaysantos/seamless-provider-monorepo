<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class PcaLogoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.playgame RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_logout_validRequest_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'player001',
            'token' => 'PCAUCN_TOKEN88888888',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_TOKEN88888888'
        ];

        $response = $this->post('pca/prov/logout', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b'
        ]);

        $this->assertDatabaseMissing('pca.playgame', [
            'play_id' => 'player001',
            'token' => 'PCAUCN_TOKEN88888888',
            'expired' => 'FALSE'
        ]);
    }

    #[DataProvider('logoutParams')]
    public function test_logout_invalidRequest_expectedData($unset, $token)
    {
        $payload = [
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_TOKEN88888888'
        ];

        unset($payload[$unset]);

        $response = $this->post('pca/prov/logout', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => $token,
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ]);
    }

    public static function logoutParams()
    {
        return [
            ['requestId', ''],
            ['username', 'f2b26f85-021e-4326-80cf-490932c45a2b'],
            ['externalToken', 'f2b26f85-021e-4326-80cf-490932c45a2b'],
        ];
    }

    public function test_logout_playerNotFound_expectedData()
    {
        $payload = [
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_TOKEN88888888'
        ];

        $response = $this->post('pca/prov/logout', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);
    }

    public function test_logout_usernameWithoutKiosk_expectedData()
    {
        $payload = [
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'username' => 'invalidUsername',
            'externalToken' => 'PCAUCN_TOKEN88888888'
        ];

        $response = $this->post('pca/prov/logout', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);
    }

    public function test_logout_invalidToken_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'player001',
            'token' => 'PCAUCN_TOKEN88888888',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'INVALID_TOKEN'
        ];

        $response = $this->post('pca/prov/logout', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'error' => [
                'code' => 'ERR_AUTHENTICATION_FAILED'
            ]
        ]);
    }
}
