<?php

use Tests\TestCase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;

class BesUpdateGamePositionTest extends TestCase
{
    public function test_updateGamePosition_validRequest_expectedData()
    {
        Http::fake([
            '/api/game/subgamelist' => Http::response(json_encode([
                'gamelist' => [
                    (object)[
                        'gid' => 'test1',
                        'SortID' => 3
                    ],
                    (object)[
                        'gid' => 'test2',
                        'SortID' => 2
                    ],
                    (object)[
                        'gid' => 'test3',
                        'SortID' => 1
                    ],
                ]
            ]))
        ]);

        Http::fake([
            '/api/games/update-game-position' => Http::response(json_encode([
                'code' => 9401
            ]))
        ]);

        $response = $this->post('bes/in/update-game-position', [], [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'Success',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.stag-topgame.com/api/game/subgamelist' &&
                $request['extension1'] == 'besoftaixswuat';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'http://12.0.129.164/api/games/update-game-position' &&
                $request['providerCode'] == 'BES' &&
                $request['gameCode'] == ['test3', 'test2', 'test1'];
        });
    }

    public function test_updateGamePosition_validRequestDifferentSorting_expectedData()
    {
        Http::fake([
            '/api/game/subgamelist' => Http::response(json_encode([
                'gamelist' => [
                    (object)[
                        'gid' => 'test1',
                        'SortID' => 2
                    ],
                    (object)[
                        'gid' => 'test2',
                        'SortID' => 1
                    ],
                    (object)[
                        'gid' => 'test3',
                        'SortID' => 3
                    ],
                ]
            ]))
        ]);

        Http::fake([
            '/api/games/update-game-position' => Http::response(json_encode([
                'code' => 9401
            ]))
        ]);

        $response = $this->post('bes/in/update-game-position', [], [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'Success',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.stag-topgame.com/api/game/subgamelist' &&
                $request['extension1'] == 'besoftaixswuat';
        });

        Http::assertSent(function ($request) {
            return $request->url() == 'http://12.0.129.164/api/games/update-game-position' &&
                $request['providerCode'] == 'BES' &&
                $request['gameCode'] == ['test2', 'test1', 'test3'];
        });
    }

    public function test_updateGamePosition_gameListApiFailed_thirdPartyException()
    {
        Http::fake([
            '/api/game/subgamelist' => Http::response('')
        ]);

        Http::fake([
            '/api/games/update-game-position' => Http::response(json_encode([
                'code' => 9401
            ]))
        ]);

        $response = $this->post('bes/in/update-game-position', [], [
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

    #[DataProvider('gameListApiParams')]
    public function test_updateGamePosition_gameListApiInvalidResponse_thirdPartyException($param, $value)
    {
        $apiResponse = [
            'gamelist' => [
                (object)[
                    'gid' => 'test1',
                    'SortID' => 3
                ]
            ]
        ];

        if ($param === 'gamelist')
            $apiResponse[$param] = $value;
        else
            $apiResponse['gamelist'][0]->$param = $value;

        Http::fake([
            '/api/game/subgamelist' => Http::response(json_encode($apiResponse))
        ]);

        Http::fake([
            '/api/games/update-game-position' => Http::response(json_encode([
                'code' => 9401
            ]))
        ]);

        $response = $this->post('bes/in/update-game-position', [], [
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

    public static function gameListApiParams()
    {
        return [
            ['gamelist', 'test'],
            ['gid', 123],
            ['SortID', 'test']
        ];
    }

    #[DataProvider('navApiParams')]
    public function test_updateGamePosition_updateGamePositionInvalidResponse_thirdPartyException($value)
    {
        Http::fake([
            '/api/game/subgamelist' => Http::response(json_encode([
                'gamelist' => [
                    (object)[
                        'gid' => 'test1',
                        'SortID' => 3
                    ],
                    (object)[
                        'gid' => 'test2',
                        'SortID' => 2
                    ],
                    (object)[
                        'gid' => 'test3',
                        'SortID' => 1
                    ],
                ]
            ]))
        ]);

        Http::fake([
            '/api/games/update-game-position' => Http::response(json_encode([
                'code' => $value
            ]))
        ]);

        $response = $this->post('bes/in/update-game-position', [], [
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

    public static function navApiParams()
    {
        return [
            [9402],
            ['test']
        ];
    }
}
