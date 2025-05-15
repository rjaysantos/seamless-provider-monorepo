<?php

use Tests\TestCase;
use App\Models\Cq9Player;
use App\Models\Cq9Report;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class Cq9VisualTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE cq9.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE cq9.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_visual_stgValidRequest_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        Http::fake([
            '/gameboy/order/detail/v2*' => Http::response(json_encode([
                'data' => 'test-url',
                'status' => [
                    'code' => 0
                ],
            ]))
        ]);

        $response = $this->post('cq9/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => true,
            'code' => 200,
            'data' => 'test-url',
            'error' => null
        ]);

        $response->assertStatus(200);

        Http::assertSent(function ($request) {
            return $request->url() == 'https://api.cqgame.games/gameboy/order/detail/v2?roundid=testTransactionID&account=testPlayID' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI2NmIxNWI1YjMzY2NjZDUwYTJhMDZkMDgiLCJhY2NvdW50IjoidGVzdF9hZ2VudF9pZHIiLCJvd25lciI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsInBhcmVudCI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsImN1cnJlbmN5IjoiSURSIiwiYnJhbmQiOiJjcTkiLCJqdGkiOiI1ODA0Nzg2NzkiLCJpYXQiOjE3MjI4OTkyOTEsImlzcyI6IkN5cHJlc3MiLCJzdWIiOiJTU1Rva2VuIn0.Eel-IlWgB5JColzIjP5TFUwUzV-7D16-nnfl7--jUFo') &&
                $request['roundid'] == 'testTransactionID' &&
                $request['account'] == 'testPlayID';
        });
    }

    // public function test_visual_prodValidRequest_expectedData() {}

    public function test_visual_invalidBearerToken_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        $response = $this->post('cq9/in/visual', $request, [
            'Authorization' => 'Bearer ' . 'invalidBearerToken',
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 9301,
            'error' => 'Authorization failed.',
            'data' => null
        ]);

        $response->assertStatus(401);
    }

    public function test_visual_playerNotFound_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID'
        ]);

        $request = [
            'play_id' => 'invalidPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        $response = $this->post('cq9/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Player not found',
            'data' => null,
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_transactionNotFound_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'invalidTransaction',
            'currency' => 'IDR',
        ];

        $response = $this->post('cq9/in/visual', $request, [
            'Authorization' => 'Bearer ' . env('FEATURE_TEST_TOKEN'),
        ]);

        $response->assertJson([
            'success' => false,
            'code' => 422,
            'error' => 'Transaction not found',
            'data' => null,
        ]);

        $response->assertStatus(200);
    }

    public function test_visual_thirdPartyApiInvalidResponse_expectedData()
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        Http::fake([
            '/gameboy/order/detail/v2*' => Http::response(json_encode([
                'data' => null,
                'status' => [
                    'code' => 123
                ],
            ]))
        ]);

        $response = $this->post('cq9/in/visual', $request, [
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
            return $request->url() == 'https://api.cqgame.games/gameboy/order/detail/v2?roundid=testTransactionID&account=testPlayID' &&
                $request->hasHeader('Authorization', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyaWQiOiI2NmIxNWI1YjMzY2NjZDUwYTJhMDZkMDgiLCJhY2NvdW50IjoidGVzdF9hZ2VudF9pZHIiLCJvd25lciI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsInBhcmVudCI6IjVkOGMxYzkzNDFlMTBkMDAwMThmM2MxYiIsImN1cnJlbmN5IjoiSURSIiwiYnJhbmQiOiJjcTkiLCJqdGkiOiI1ODA0Nzg2NzkiLCJpYXQiOjE3MjI4OTkyOTEsImlzcyI6IkN5cHJlc3MiLCJzdWIiOiJTU1Rva2VuIn0.Eel-IlWgB5JColzIjP5TFUwUzV-7D16-nnfl7--jUFo') &&
                $request['roundid'] == 'testTransactionID' &&
                $request['account'] == 'testPlayID';
        });
    }

    /**
     * @dataProvider visualParams
     */
    public function test_visual_incompleteParameters_expectedData($param)
    {
        Cq9Player::factory()->create([
            'play_id' => 'testPlayID'
        ]);

        Cq9Report::factory()->create([
            'trx_id' => 'testTransactionID'
        ]);

        $request = [
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ];

        unset($request[$param]);

        $response = $this->post('cq9/in/visual', $request, [
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

    public static function visualParams()
    {
        return [
            ['play_id'],
            ['bet_id'],
            ['currency']
        ];
    }
}
