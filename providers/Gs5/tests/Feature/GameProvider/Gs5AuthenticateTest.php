<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;

class Gs5AuthenticateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE gs5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE gs5.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE gs5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_authenticate_validRequest_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('gs5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE'
        ]);

        $request = ['access_token' => 'testToken'];

        $response = $this->get(uri: 'gs5/prov/api/authenticate/?' . http_build_query($request));

        $response->assertJson([
            'status_code' => 0,
            'member_id' => 'testPlayID',
            'member_name' => 'testUsername',
            'balance' => 100000.00
        ]);

        $response->assertStatus(200);
    }

    public function test_authenticate_invalidRequest_expectedData()
    {
        $request = [];

        $response = $this->get(uri: 'gs5/prov/api/authenticate/?' . http_build_query($request));

        $response->assertJson(['status_code' => 7]);

        $response->assertStatus(200);
    }

    public function test_authenticate_tokenNotFound_expectedData()
    {
        DB::table('gs5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE'
        ]);

        $request = ['access_token' => 'invalidToken'];

        $response = $this->get(uri: 'gs5/prov/api/authenticate/?' . http_build_query($request));

        $response->assertJson(['status_code' => 1]);

        $response->assertStatus(200);
    }

    public function test_authenticate_walletError_expectedData()
    {
        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 551.534
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        DB::table('gs5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('gs5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE'
        ]);

        $request = ['access_token' => 'testToken'];

        $response = $this->get(uri: 'gs5/prov/api/authenticate/?' . http_build_query($request));

        $response->assertJson(['status_code' => 8]);

        $response->assertStatus(200);
    }
}