<?php

use Tests\TestCase;
use App\Models\PlaPlayer;
use App\Models\PlaReport;
use App\Contracts\IRandomizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PlaVisualTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE pla.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pla.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pla.playgame RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_visual_validRequest_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 0,
            'win_amount' => 200,
            'created_at' => '2021-01-02 08:00:20',
            'updated_at' => '2021-01-02 08:00:20',
            'ref_id' => 'testTransactionID3'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID3',
            'currency' => 'IDR',
        ];

        Http::fake([
            'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
            'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30'
            => Http::response([
                    "data" => [
                        [
                            "provider" => "playtech",
                            "transaction_id" => "sampleTransactionId",
                            "casino" => [
                                "short_name" => "testCasino",
                                "name" => "testCasino"
                            ],
                            "game_name" => "testGameName",
                            "player" => [
                                "username" => "testUsername",
                                "id" => 1,
                                "casino_user_id" => "testPlayID",
                                "unique_id" => "O6O-O1n"
                            ],
                            "round_history" => [
                                "transaction_uuid" => "sampleTransactionIdUuid",
                                "game_history_url" => "test_url?token=testToken"
                            ],
                            "currency" => "IDR",
                            "bet" => 100,
                            "win" => 200,
                            "jackpot_win" => null,
                            "refund" => 0,
                            "in_jackpot" => 0,
                            "desc" => null,
                            "transfer_fund" => 0,
                            "round_id" => "testRoundID",
                            "created_at" => "2024-10-02 00:00:20"
                        ]
                    ]
                ], 201)
        ]);

        app()->bind(IRandomizer::class, function () {
            return new class implements IRandomizer {
                public function createToken(): string
                {
                    return 'testToken';
                }
            };
        });

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test_url?token=testToken',
            'error' => null
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
                'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30' &&
                $request->hasHeader('x-api-key', '14b1691fb273a69f33c888ba5ffe9d900634d866bd9426c9e0e2982f7ed25bf0') &&
                $request['page_size'] == 5000 &&
                $request['dateRange']['startDate'] == '2021-01-02 00:00:10' &&
                $request['dateRange']['endDate'] == '2021-01-02 00:00:30';
        });
    }

    /**
     * @dataProvider visualParams
     */
    public function test_visual_invalidRequest_expectedData($unset)
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '1-testRoundID',
            'currency' => 'IDR',
        ];

        unset($request[$unset]);

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'invalid request format'
        ]);
    }

    public static function visualParams()
    {
        return [
            ['play_id'],
            ['bet_id'],
            ['currency'],
        ];
    }

    public function test_visual_invalidToken_expectedData()
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '1-testRoundID',
            'currency' => 'IDR',
        ];

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Invalid Token',
        ]);

        $response->assertStatus(401);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);
    }

    public function test_visual_playerNotFound_expectedData()
    {
        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '1-testRoundID',
            'currency' => 'IDR',
        ];

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Player not found'
        ]);
    }

    public function test_visual_transactionNotFound_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '1-testRoundID',
            'currency' => 'IDR',
        ];

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Transaction not found'
        ]);
    }

    public function test_visual_thirdPartyApiError_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 0,
            'win_amount' => 200,
            'created_at' => '2021-01-02 08:00:20',
            'updated_at' => '2021-01-02 08:00:20',
            'ref_id' => 'testTransactionID3'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID3',
            'currency' => 'IDR',
        ];

        Http::fake([
            'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
            'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30'
            => Http::response([], 401)
        ]);

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        Http::assertSent(function ($request) { //Temp
            return $request->url() == 'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
                'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30' &&
                $request->hasHeader('x-api-key', '14b1691fb273a69f33c888ba5ffe9d900634d866bd9426c9e0e2982f7ed25bf0') &&
                $request['page_size'] == 5000 &&
                $request['dateRange']['startDate'] == '2021-01-02 00:00:10' &&
                $request['dateRange']['endDate'] == '2021-01-02 00:00:30';
        });
    }

    public function test_visual_emptyDataThirdPartyApiError_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 0,
            'win_amount' => 200,
            'created_at' => '2021-01-02 08:00:20',
            'updated_at' => '2021-01-02 08:00:20',
            'ref_id' => 'testTransactionID3'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID3',
            'currency' => 'IDR',
        ];

        Http::fake([
            'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
            'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30'
            => Http::response([
                    "data" => []
                ], 201)
        ]);

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) { //Temp
            return $request->url() == 'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
                'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30' &&
                $request->hasHeader('x-api-key', '14b1691fb273a69f33c888ba5ffe9d900634d866bd9426c9e0e2982f7ed25bf0') &&
                $request['page_size'] == 5000 &&
                $request['dateRange']['startDate'] == '2021-01-02 00:00:10' &&
                $request['dateRange']['endDate'] == '2021-01-02 00:00:30';
        });

        Http::assertSent(function ($request) { //Temp
            return $request->url() == 'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
                'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A00&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A40' &&
                $request->hasHeader('x-api-key', '14b1691fb273a69f33c888ba5ffe9d900634d866bd9426c9e0e2982f7ed25bf0') &&
                $request['page_size'] == 5000 &&
                $request['dateRange']['startDate'] == '2021-01-02 00:00:00' &&
                $request['dateRange']['endDate'] == '2021-01-02 00:00:40';
        });

        Http::assertSent(function ($request) { //Temp
            return $request->url() == 'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
                'dateRange%5BstartDate%5D=2021-01-01%2023%3A59%3A50&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A50' &&
                $request->hasHeader('x-api-key', '14b1691fb273a69f33c888ba5ffe9d900634d866bd9426c9e0e2982f7ed25bf0') &&
                $request['page_size'] == 5000 &&
                $request['dateRange']['startDate'] == '2021-01-01 23:59:50' &&
                $request['dateRange']['endDate'] == '2021-01-02 00:00:50';
        });
    }

    public function test_visual_dataParameterMissingThirdPartyApiError_expectedData()
    {
        PlaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PlaReport::factory()->create([
            'trx_id' => 'testRoundID',
            'bet_amount' => 0,
            'win_amount' => 200,
            'created_at' => '2021-01-02 08:00:20',
            'updated_at' => '2021-01-02 08:00:20',
            'ref_id' => 'testTransactionID3'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID3',
            'currency' => 'IDR',
        ];

        Http::fake([
            'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
            'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30'
            => Http::response([
                    'links' => [],
                    'meta' => []
                ], 201)
        ]);

        $response = $this->post('pla/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) { //Temp
            return $request->url() == 'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
                'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30' &&
                $request->hasHeader('x-api-key', '14b1691fb273a69f33c888ba5ffe9d900634d866bd9426c9e0e2982f7ed25bf0') &&
                $request['page_size'] == 5000 &&
                $request['dateRange']['startDate'] == '2021-01-02 00:00:10' &&
                $request['dateRange']['endDate'] == '2021-01-02 00:00:30';
        });
    }
}
