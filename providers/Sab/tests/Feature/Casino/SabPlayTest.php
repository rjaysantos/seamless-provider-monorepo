<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Libraries\Wallet\V2\TestWallet;
use PHPUnit\Framework\Attributes\DataProvider;

class SabPlayTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_play_stgValidRequestNoPlayer_successReturn()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        Http::fake([
            'api/CreateMember' => Http::response(json_encode([
                'error_code' => 0
            ]))
        ]);

        Http::fake([
            'api/GetSabaUrl' => Http::response(json_encode([
                'error_code' => 0,
                'Data' => 'test_url?token=test'
            ]))
        ]);

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_url?token=test&lang=en&OType=3',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.players', [
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://p1b3tsa.bw6688.com/api/CreateMember' &&
                $request['vendor_id'] == '96l542m8kr' &&
                $request['vendor_member_id'] == 'AIX_testPlayID_test' &&
                $request['username'] == 'AIX_testPlayID_test' &&
                $request['currency'] == 20;
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://p1b3tsa.bw6688.com/api/GetSabaUrl' &&
                $request['vendor_id'] == '96l542m8kr' &&
                $request['vendor_member_id'] == 'AIX_testPlayID_test' &&
                $request['platform'] == 1;
        });
    }

    public function test_play_stgValidRequestPlayerAlreadyExist_successReturn()
    {
        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        Http::fake([
            'api/GetSabaUrl' => Http::response(json_encode([
                'error_code' => 0,
                'Data' => 'test_url?token=test'
            ]))
        ]);

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_url?token=test&lang=en&OType=3',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.players', [
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://p1b3tsa.bw6688.com/api/GetSabaUrl' &&
                $request['vendor_id'] == '96l542m8kr' &&
                $request['vendor_member_id'] == 'AIX_testPlayID_test' &&
                $request['platform'] == 1;
        });
    }

    #[DataProvider('playParams')]
    public function test_play_incompleteRequestParameters_invalidRequestResponse($param)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        unset($request[$param]);

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'code' => 422,
            'data' => NULL,
            'error' => "invalid request format",
        ]);

        $response->assertStatus(200);
    }

    public static function playParams()
    {
        return [
            ['playId'],
            ['username'],
            ['currency'],
            ['language'],
            ['device']
        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'invalid_bearer_token',
        ]);

        $response->assertJson([
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => NULL,
        ]);

        $response->assertStatus(401);
    }

    public function test_play_getSabaUrlThirdPartyError_thirdPartyErrorResponse()
    {
        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        Http::fake([
            'api/GetSabaUrl' => Http::response('', 500)
        ]);

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    public function test_play_createMemberThirdPartyError_thirdPartyErrorResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        Http::fake([
            'api/CreateMember' => Http::response('', 500)
        ]);

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.players', [
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
        ]);
    }

    public function test_play_createMemberInvalidResponseFormat_thirdPartyErrorResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        Http::fake([
            'api/CreateMember' => Http::response(json_encode([
                'error_code' => "invalid_response"
            ]))
        ]);

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.players', [
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
        ]);
    }

    public function test_play_getSabaUrlInvalidResponseFormat_thirdPartyErrorResponse()
    {
        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        Http::fake([
            'api/GetSabaUrl' => Http::response(json_encode([
                'error_code' => "invalid_response"
            ]))
        ]);

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }

    public function test_play_createMemberResponseErrorCodeNot0_thirdPartyErrorResponse()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        Http::fake([
            'api/CreateMember' => Http::response(json_encode([
                'error_code' => 999
            ]))
        ]);

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.players', [
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
        ]);
    }

    public function test_play_getSabaUrlResponseErrorCodeNot0_thirdPartyErrorResponse()
    {
        DB::table('sab.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'AIX_testPlayID_test',
            'currency' => 'IDR',
            'game' => 0
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ];

        Http::fake([
            'api/GetSabaUrl' => Http::response(json_encode([
                'error_code' => 999
            ]))
        ]);

        $response = $this->post('/sab/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);
    }
}
