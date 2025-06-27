<?php

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

class HcgPlayTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hcg.players RESTART IDENTITY;');
    }

    public function test_play_validDataNoPlayer_expected()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'memberId' => 123,
            'host' => 'testHost.com',
            'device' => 1,
            'memberIp' => '127.0.0.1'
        ];

        Http::fake([
            '/hcRequest' => Http::sequence()
                ->push(json_encode(['returnCode' => '0000']), 200)
                ->push(json_encode([
                    'returnCode' => '0000',
                    'data' => [
                        'path' => 'test_url?token=test'
                    ]
                ]), 200)
        ]);

        $response = $this->post('hcg/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_url?token=test',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hcg.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.hcgame888.com/hcRequest' &&
                $request['lang'] == 'en' &&
                $request['x'] == 'X50Uj5prQmjo5n3Y61N8a6vjip+fIT8r++uB8QP7j8SNG1PXzTfkrPiK7TfMRhYkZvm1jzBCkEmrXlo4kg3bE9FnM2MXZvldiV70/f5OY6EqHC01dTrQx6tIvIoWxYJzC4NBzUlhPa58M/O8ramNcwVUDPTEHmVpKJ5jUO3M5OGvGPOlb0n9baRm+Px5X1e2zwNMQLVtTg/eJRyO2OAboYBGD7oO5EVi';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.hcgame888.com/hcRequest' &&
                $request['lang'] == 'en' &&
                $request['x'] == 'X50Uj5prQmjbm8bnmk9kv8lIscyNTYDrr9MxqHKU9B5K3tJ+wAxIPNHOCxcSTZ8cPQXoeOXIpVVswPslWjTf4LzkgKgGIstg2zI+oEoNomCkKUqzxE0IVjpL8h79+hYo8Z+aCBM6MtEsr7b061yOIJjOpTQ7co8MN1hl4aqJ2Vy3CXQ8QnkAByCbGbDzbjMPxJYAtiNflMq6rEmAZlbKL4rUN+6WOmcmWwl9Ia2GvHM=';
        });
    }

    public function test_play_validDataHasPlayer_expected()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'memberId' => 123,
            'host' => 'testHost.com',
            'device' => 1,
            'memberIp' => '127.0.0.1'
        ];

        DB::table('hcg.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::fake([
            '/hcRequest' => Http::response(json_encode([
                'returnCode' => "0000",
                'data' => [
                    'path' => 'test_url?token=test'
                ]
            ]))
        ]);

        $response = $this->post('hcg/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_url?token=test',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertNotSent(function ($request) {
            return $request->url() == 'https://api.hcgame888.com/hcRequest' &&
                $request['lang'] == 'en' &&
                $request['x'] == 'X50Uj5prQmjo5n3Y61N8a6vjip+fIT8r++uB8QP7j8SNG1PXzTfkrPiK7TfMRhYkZvm1jzBCkEmrXlo4kg3bE9FnM2MXZvldiV70/f5OY6EqHC01dTrQx6tIvIoWxYJzC4NBzUlhPa58M/O8ramNcwVUDPTEHmVpKJ5jUO3M5OGvGPOlb0n9baRm+Px5X1e2zwNMQLVtTg/eJRyO2OAboYBGD7oO5EVi';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.hcgame888.com/hcRequest' &&
                $request['lang'] == 'en' &&
                $request['x'] == 'X50Uj5prQmjbm8bnmk9kv8lIscyNTYDrr9MxqHKU9B5K3tJ+wAxIPNHOCxcSTZ8cPQXoeOXIpVVswPslWjTf4LzkgKgGIstg2zI+oEoNomCkKUqzxE0IVjpL8h79+hYo8Z+aCBM6MtEsr7b061yOIJjOpTQ7co8MN1hl4aqJ2Vy3CXQ8QnkAByCbGbDzbjMPxJYAtiNflMq6rEmAZlbKL4rUN+6WOmcmWwl9Ia2GvHM=';
        });
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequest_expectedData($unset)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'memberId' => 123,
            'host' => 'testHost.com',
            'device' => 1,
            'memberIp' => '127.0.0.1'
        ];

        unset($request[$unset]);

        $response = $this->post('hcg/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'invalid request format'
        ]);

        $response->assertStatus(200);
    }

    public static function playParams()
    {
        return [
            ['playId'],
            ['username'],
            ['currency'],
            ['gameId'],
            ['language'],
            ['memberId'],
            ['host'],
            ['device'],
            ['memberIp']
        ];
    }

    public function test_play_invalidBearerToken_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'memberId' => 123,
            'host' => 'testHost.com',
            'device' => 1,
            'memberIp' => '127.0.0.1'
        ];

        $response = $this->post('hcg/in/play', $request, [
            'Authorization' => 'Invalid Bearer Token',
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);

        $response->assertStatus(401);
    }

    public function test_play_errorCodeNot0000Register_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'memberId' => 123,
            'host' => 'testHost.com',
            'device' => 1,
            'memberIp' => '127.0.0.1'
        ];

        Http::fake([
            '/hcRequest' => Http::response(json_encode([
                'returnCode' => "8005"
            ]))
        ]);

        $response = $this->post('hcg/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hcg.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);
    }

    public function test_play_errorCodeNot0000Login_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'memberId' => 123,
            'host' => 'testHost.com',
            'device' => 1,
            'memberIp' => '127.0.0.1'
        ];

        Http::fake([
            '/hcRequest' => Http::sequence()
                ->push(json_encode(['returnCode' => '0000']), 200)
                ->push(json_encode([
                    'returnCode' => '8005',
                    'data' => []
                ]), 200)
        ]);

        $response = $this->post('hcg/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hcg.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);
    }

    public function test_play_invalidThirdPartyApiResponseRegister_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'memberId' => 123,
            'host' => 'testHost.com',
            'device' => 1,
            'memberIp' => '127.0.0.1'
        ];

        Http::fake([
            '/hcRequest' => Http::response(json_encode([
                'returnCode' => null
            ]))
        ]);

        $response = $this->post('hcg/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('hcg.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);
    }

    #[DataProvider('userLoginInterfaceResponseParams')]
    public function test_play_invalidThirdPartyApiResponseLogin_expectedData($unset)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'memberId' => 123,
            'host' => 'testHost.com',
            'device' => 1,
            'memberIp' => '127.0.0.1'
        ];

        $response = [
            'returnCode' => '0000',
            'data' => [
                'path' => 'test_url?token=test'
            ]
        ];

        if (isset($response[$unset]) === true)
            unset($response[$unset]);
        else
            unset($response['data'][$unset]);

        Http::fake([
            '/hcRequest' => Http::sequence()
                ->push(['returnCode' => '0000'], 200)
                ->push($response, 200)
        ]);

        $response = $this->post('hcg/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('hcg.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);
    }

    public static function userLoginInterfaceResponseParams()
    {
        return [
            ['returnCode'],
            ['data'],
            ['path']
        ];
    }
}
