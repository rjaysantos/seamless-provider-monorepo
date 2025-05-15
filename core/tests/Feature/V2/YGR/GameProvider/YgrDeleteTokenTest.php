<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;

class YgrDeleteTokenTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE ygr.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE ygr.playgame RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_deleteToken_validRequest_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE',
            'status' => 'testGameID'
        ]);

        $request = [
            'connectToken' => 'testToken'
        ];

        $response = $this->post('ygr/prov/token/delConnectToken', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => '0',
                'message' => 'Success',
                'dateTime' => '2021-01-01T00:00:00+08:00',
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('ygr.playgame', [
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE',
            'status' => 'testGameID'
        ]);

        Carbon::setTestNow();
    }

    public function test_deleteToken_invalidRequest_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE',
            'status' => 'testGameID'
        ]);

        $request = [
            'connectToken' => 123456
        ];

        $response = $this->post('ygr/prov/token/delConnectToken', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => '201',
                'message' => 'Bad parameter',
                'dateTime' => '2021-01-01T00:00:00+08:00',
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }

    public function test_deleteToken_tokenNotFoundException_expectedData()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        DB::table('ygr.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('ygr.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testToken',
            'expired' => 'FALSE',
            'status' => 'testGameID'
        ]);

        $request = [
            'connectToken' => 'invalidToken'
        ];

        $response = $this->post('ygr/prov/token/delConnectToken', $request);

        $response->assertJson([
            'data' => [],
            'status' => [
                'code' => 102,
                'Message' => 'Sign Invalid',
                'dateTime' => '2021-01-01T00:00:00+08:00',
                // 'traceCode' => Str::uuid()->toString(),
            ]
        ]);

        $response->assertStatus(200);

        Carbon::setTestNow();
    }
}