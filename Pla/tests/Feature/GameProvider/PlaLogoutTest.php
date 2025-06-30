<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class PlaLogoutTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pla.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_logout_validRequest_expectedData()
    {
        DB::table('pla.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR',
            'token' => 'PLAUC_TOKEN88888888',
        ]);

        $payload = [
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888'
        ];

        $response = $this->post('pla/prov/logout', $payload);


        $response->assertJson([
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('pla.players', [
            'play_id' => 'player001',
            'token' => 'PLAUC_TOKEN88888888'
        ]);

        $this->assertDatabaseHas('pla.players', [
            'play_id' => 'player001',
            'token' => null,
        ]);
    }

    #[DataProvider('logoutParams')]
    public function test_logout_invalidRequest_expectedData($unset, $token)
    {
        $payload = [
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888'
        ];

        unset($payload[$unset]);

        $response = $this->post('pla/prov/logout', $payload);

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
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN88888888'
        ];

        $response = $this->post('pla/prov/logout', $payload);

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
            'externalToken' => 'PLAUC_TOKEN88888888'
        ];

        $response = $this->post('pla/prov/logout', $payload);

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
        DB::table('pla.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR',
            'token' => 'PLAUC_TOKEN88888888'
        ]);

        $payload = [
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'INVALID_TOKEN'
        ];

        $response = $this->post('pla/prov/logout', $payload);

        $response->assertJson([
            'requestId' => 'f2b26f85-021e-4326-80cf-490932c45a2b',
            'error' => [
                'code' => 'ERR_AUTHENTICATION_FAILED'
            ]
        ]);

        $response->assertStatus(200);
    }
}
