<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class PcaAuthenticateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.playgame RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_authenticate_validRequest_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'player001',
            'token' => 'PCAUCN_TOKEN123456789',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_TOKEN123456789'
        ];

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_PLAYER001',
            'currencyCode' => 'CNY',
            'countryCode' => 'CN'
        ]);
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_invalidRequest_expectedData($unset, $token)
    {
        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_TOKEN123456789'
        ];

        unset($payload[$unset]);

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => $token,
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ]);
    }

    public static function authenticateParams()
    {
        return [
            ['requestId', ''],
            ['username', 'e9ccd456-4c6a-47b3-922f-66a5e5e13513'],
            ['externalToken', 'e9ccd456-4c6a-47b3-922f-66a5e5e13513']
        ];
    }

    public function test_authenticate_playerNotFound_expectedData()
    {
        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'PCAUCN_TOKEN123456789'
        ];

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);
    }

    public function test_authenticate_usernameWithoutKiosk_expectedData()
    {
        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'invalidUsername',
            'externalToken' => 'PCAUCN_TOKEN123456789'
        ];

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);
    }

    public function test_authenticate_invalidToken_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'player001',
            'token' => 'PCAUCN_TOKEN123456789',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_PLAYER001',
            'externalToken' => 'INVALID_TOKEN'
        ];

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'error' => [
                'code' => 'ERR_AUTHENTICATION_FAILED'
            ]
        ]);
    }
}
