<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class PlaAuthenticateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE pla.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    #[DataProvider('currencyAndCountryCodes')]
    public function test_authenticate_validRequestStgSupportedCurrency_expectedData($currency)
    {
        DB::table('pla.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => $currency,
            'token' => 'PLAUC_TOKEN123456789'
        ]);

        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        $response = $this->post('pla/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PLAUC_PLAYER001',
            'currencyCode' => 'CNY',
            'countryCode' => 'CN'
        ]);
    }

    #[DataProvider('currencyAndCountryCodes')]
    public function test_authenticate_validRequestProdSupportedCurrencies_expectedData($currency, $countryCode)
    {
        config(['app.env' => 'PRODUCTION']);

        DB::table('pla.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => $currency,
            'token' => 'PLAUC_TOKEN123456789'
        ]);

        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        $response = $this->post('pla/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PLAUC_PLAYER001',
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
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        unset($payload[$unset]);

        $response = $this->post('pla/prov/authenticate', $payload);

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
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        $response = $this->post('pla/prov/authenticate', $payload);

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
            'externalToken' => 'PLAUC_TOKEN123456789'
        ];

        $response = $this->post('pla/prov/authenticate', $payload);

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
        DB::table('pla.players')->insert([
            'play_id' => 'player001',
            'username' => 'testPlayer',
            'currency' => 'IDR'
        ]);

        $payload = [
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'username' => 'PLAUC_PLAYER001',
            'externalToken' => 'INVALID_TOKEN'
        ];

        $response = $this->post('pla/prov/authenticate', $payload);

        $response->assertStatus(200);

        $response->assertJson([
            'requestId' => 'e9ccd456-4c6a-47b3-922f-66a5e5e13513',
            'error' => [
                'code' => 'ERR_AUTHENTICATION_FAILED'
            ]
        ]);
    }
}
