<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Cq9Player;
use Illuminate\Support\Facades\DB;

class Cq9CheckAccountTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE cq9.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE cq9.players RESTART IDENTITY;');
        parent::tearDown();
    }

    public function test_checkAccount_validRequest_expectedData()
    {
        Carbon::setTestNow('2021-01-01 12:00:00');

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $playID = 'testPlayID';

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->get("cq9/prov/player/check/{$playID}", [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => true,
            'status' => [
                'code' => '0',
                'message' => 'Success',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        Carbon::setTestNow();
    }

    public function test_checkAccount_invalidWtoken_expectedData()
    {
        Carbon::setTestNow('2021-01-01 12:00:00');

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $playID = 'testPlayID';

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->get("cq9/prov/player/check/{$playID}", [
            'wtoken' => 'invalidToken'
        ]);

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => '3',
                'message' => 'Token invalid.',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        Carbon::setTestNow();
    }

    public function test_checkAccount_accountNotExist_expectedData()
    {
        Carbon::setTestNow('2021-01-01 12:00:00');

        Cq9Player::factory()->create([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
        ]);

        $playID = 'invalidPlayer';

        Carbon::setTestNow(Carbon::parse('2020-01-01 00:00:00', '-04:00'));

        $response = $this->get("cq9/prov/player/check/{$playID}", [
            'wtoken' => env('FEATURE_TEST_TOKEN')
        ]);

        $response->assertJson([
            'data' => false,
            'status' => [
                'code' => '0',
                'message' => 'Success',
                'datetime' => Carbon::now()->setTimezone('-0400')->toRfc3339String()
            ]
        ]);

        Carbon::setTestNow();
    }
}
