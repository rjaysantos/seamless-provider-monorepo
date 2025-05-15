<?php

use App\Models\Cq9Player;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Cq9PlayTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE cq9.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE cq9.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_play_stgValidRequestNoPlayerYet_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1
        ];

        Http::fake([
            '/gameboy/player/sw/gamelink' => Http::response(json_encode([
                'data' => [
                    'url' => 'test-url',
                ],
                'status' => [
                    'code' => 0,
                ]
            ]))
        ]);

        $response = $this->post('cq9/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cq9.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.cqgame.games/gameboy/player/sw/gamelink' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI2NmIxNWI1YjMzY2NjZDUwYTJhMDZkMDgiLCJhY2NvdW50IjoidGVzdF9hZ2VudF9pZHIiLCJvd25lciI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsInBhcmVudCI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsImN1cnJlbmN5IjoiSURSIiwiYnJhbmQiOiJjcTkiLCJqdGkiOiI1ODA0Nzg2NzkiLCJpYXQiOjE3MjI4OTkyOTEsImlzcyI6IkN5cHJlc3MiLCJzdWIiOiJTU1Rva2VuIn0.Eel-IlWgB5JColzIjP5TFUwUzV-7D16-nnfl7--jUFo') &&
                $request['account'] == 'testPlayID' &&
                $request['gamehall'] == 'cq9' &&
                $request['gamecode'] == '1' &&
                $request['gameplat'] == 'WEB' &&
                $request['lang'] == 'en';
        });
    }

    public function test_play_stgValidRequestHasPlayer_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1
        ];

        Http::fake([
            '/gameboy/player/sw/gamelink' => Http::response(json_encode([
                'data' => [
                    'url' => 'test-url',
                ],
                'status' => [
                    'code' => 0,
                ]
            ]))
        ]);

        $response = $this->post('cq9/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('cq9.players', [
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.cqgame.games/gameboy/player/sw/gamelink' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI2NmIxNWI1YjMzY2NjZDUwYTJhMDZkMDgiLCJhY2NvdW50IjoidGVzdF9hZ2VudF9pZHIiLCJvd25lciI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsInBhcmVudCI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsImN1cnJlbmN5IjoiSURSIiwiYnJhbmQiOiJjcTkiLCJqdGkiOiI1ODA0Nzg2NzkiLCJpYXQiOjE3MjI4OTkyOTEsImlzcyI6IkN5cHJlc3MiLCJzdWIiOiJTU1Rva2VuIn0.Eel-IlWgB5JColzIjP5TFUwUzV-7D16-nnfl7--jUFo') &&
                $request['account'] == 'testPlayID' &&
                $request['gamehall'] == 'cq9' &&
                $request['gamecode'] == '1' &&
                $request['gameplat'] == 'WEB' &&
                $request['lang'] == 'en';
        });
    }

    // public function test_play_prodValidRequestNoPlayer_expectedData()

    public function test_play_invalidBearerToken_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '1'
        ];

        $response = $this->post('cq9/in/play', $request, [
            'Authorization' => 'invalid_bearer_token',
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);

        $response->assertStatus(401);
    }

    public function test_play_thirdPartyInvalidResponse_expectedData()
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1
        ];

        Http::fake([
            '/gameboy/player/sw/gamelink' => Http::response(json_encode([
                'data' => [
                    'url' => null,
                ],
                'status' => [
                    'code' => 123,
                ]
            ]))
        ]);

        $response = $this->post('cq9/in/play', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.cqgame.games/gameboy/player/sw/gamelink' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI2NmIxNWI1YjMzY2NjZDUwYTJhMDZkMDgiLCJhY2NvdW50IjoidGVzdF9hZ2VudF9pZHIiLCJvd25lciI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsInBhcmVudCI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsImN1cnJlbmN5IjoiSURSIiwiYnJhbmQiOiJjcTkiLCJqdGkiOiI1ODA0Nzg2NzkiLCJpYXQiOjE3MjI4OTkyOTEsImlzcyI6IkN5cHJlc3MiLCJzdWIiOiJTU1Rva2VuIn0.Eel-IlWgB5JColzIjP5TFUwUzV-7D16-nnfl7--jUFo') &&
                $request['account'] == 'testPlayID' &&
                $request['gamehall'] == 'cq9' &&
                $request['gamecode'] == '1' &&
                $request['gameplat'] == 'WEB' &&
                $request['lang'] == 'en';
        });
    }

    /**
     * @dataProvider playParams
     */
    public function test_play_incompleteRequestParameters_expectedData($param)
    {
        $request = [
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => '1',
            'device' => 1
        ];

        unset($request[$param]);

        $response = $this->post('cq9/in/play', $request, [
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
            ['language'],
            ['gameId'],
            ['device']
        ];
    }
}
