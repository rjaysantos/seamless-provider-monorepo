<?php

use Tests\TestCase;
use App\Models\PcaPlayer;
use App\Models\PcaReport;
use App\Contracts\IRandomizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PcaVisualTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE pca.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE pca.playgame RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_visual_validRequest_expectedData()
    {
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-02 08:00:20',
            'bet_id' => 'testRoundID',
            'wager_amount' => 0,
            'payout_amount' => 200,
            'status' => 'WAGER',
            'ref_id' => 'testTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => 'testTransactionID',
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
                        "created_at" => "2024-10-2 00:00:20"
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

        $response = $this->post('pca/in/visual', $request, [
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
                $request->hasHeader('x-api-key', '7c14553b94179ffecce68c1c8b5d588fdc028d82962beccb8c1497288d8b0e75') &&
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
            'txn_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        unset($request[$unset]);

        $response = $this->post('pca/in/visual', $request, [
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
            'txn_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        $response = $this->post('pca/in/visual', $request, [
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
            'txn_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        $response = $this->post('pca/in/visual', $request, [
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
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => '1-testRoundID',
            'txn_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        $response = $this->post('pca/in/visual', $request, [
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
        PcaPlayer::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        PcaReport::factory()->create([
            'play_id' => 'testPlayID',
            'currency' => 'IDR',
            'game_code' => 'test;testGameID',
            'bet_choice' => '-',
            'bet_time' => '2021-01-02 08:00:20',
            'bet_id' => 'testRoundID',
            'wager_amount' => 0,
            'payout_amount' => 200,
            'status' => 'WAGER',
            'ref_id' => 'testTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        Http::fake([
            'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
                'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30'
            => Http::response([], 401)
        ]);

        $response = $this->post('pca/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertStatus(200);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'data' => null,
            'error' => 'Third Party Api error'
        ]);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.torrospins.com/api/casino/statistics/bet-history?page_size=5000&' .
                'dateRange%5BstartDate%5D=2021-01-02%2000%3A00%3A10&dateRange%5BendDate%5D=2021-01-02%2000%3A00%3A30' &&
                $request->hasHeader('x-api-key', '7c14553b94179ffecce68c1c8b5d588fdc028d82962beccb8c1497288d8b0e75') &&
                $request['page_size'] == 5000 &&
                $request['dateRange']['startDate'] == '2021-01-02 00:00:10' &&
                $request['dateRange']['endDate'] == '2021-01-02 00:00:30';
        });
    }
}
