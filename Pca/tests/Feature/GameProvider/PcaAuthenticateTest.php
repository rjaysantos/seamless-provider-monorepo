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

    #[DataProvider('currencyAndCountryCodes')]
    public function test_authenticate_validRequestStgSupportedCurrency_expectedData($currency)
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testplayid',
            'username' => 'testPlayer',
            'currency' => $currency
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'testplayid',
            'token' => 'PCAUCN_TOKEN123456789',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN123456789'
        ];

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_TESTPLAYID',
            'currencyCode' => 'CNY',
            'countryCode' => 'CN'
        ]);
    }

    #[DataProvider('currencyAndCountryCodes')]
    public function test_authenticate_validRequestProdSupportedCurrencies_expectedData($currency, $countryCode)
    {
        config(['app.env' => 'PRODUCTION']);

        DB::table('pca.players')->insert([
            'play_id' => 'testplayid',
            'username' => 'testPlayer',
            'currency' => $currency
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'testplayid',
            'token' => 'PCAUCN_TOKEN123456789',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN123456789'
        ];

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_TESTPLAYID',
            'currencyCode' => $currency,
            'countryCode' => $countryCode
        ]);
    }

    public static function currencyAndCountryCodes()
    {
        return [
            ['IDR', 'ID'],
            ['PHP', 'PH'],
            ['VND', 'VN'],
            ['USD', 'US'],
            ['THB', 'TH'],
            ['MYR', 'MY'],
        ];
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_invalidRequest_expectedData($unset, $token)
    {
        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN123456789'
        ];

        unset($payload[$unset]);

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertJson([
            'requestId' => $token,
            'error' => [
                'code' => 'CONSTRAINT_VIOLATION'
            ]
        ]);

        $response->assertStatus(200);
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
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'PCAUCN_TOKEN123456789'
        ];

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_authenticate_usernameWithoutKiosk_expectedData()
    {
        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'invalidUsername',
            'externalToken' => 'PCAUCN_TOKEN123456789'
        ];

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'error' => [
                'code' => 'ERR_PLAYER_NOT_FOUND'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_authenticate_invalidToken_expectedData()
    {
        DB::table('pca.players')->insert([
            'play_id' => 'testplayid',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        DB::table('pca.playgame')->insert([
            'play_id' => 'testplayid',
            'token' => 'PCAUCN_TOKEN123456789',
            'expired' => 'FALSE'
        ]);

        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PCAUCN_TESTPLAYID',
            'externalToken' => 'INVALID_TOKEN'
        ];

        $response = $this->post('pca/prov/authenticate', $payload);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'error' => [
                'code' => 'ERR_AUTHENTICATION_FAILED'
            ]
        ]);

        $response->assertStatus(200);
    }
}