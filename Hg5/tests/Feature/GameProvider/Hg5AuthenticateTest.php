<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class Hg5AuthenticateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hg5.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.playgame RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hg5.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_authenticate_validRequest_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'credit' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testRandomToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testLaunchToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'launchToken' => 'testLaunchToken',
            'agentId' => 111,
            'gameId' => 'testGameID'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/authenticate',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => [
                'playerId' => 'testPlayID',
                'currency' => 'IDR',
                // 'sessionId' => 'testRandomToken', cant mock or static uuid for test
                'balance' => 1000.00
            ],
            'status' => [
                'code' => 0,
                'message' => 'success',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_invalidRequest_expectedData($parameter)
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $request = [
            'launchToken' => 'testLaunchToken',
            'agentId' => 111,
            'gameId' => 'testGameID'
        ];

        unset($request[$parameter]);

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/authenticate',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 5,
                'message' => 'Bad parameters.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public static function authenticateParams()
    {
        return [
            ['launchToken', 123],
            ['agentId', 'test'],
            ['gameId', 123]
        ];
    }

    public function test_authenticate_nullPlayerInvalidTokenException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testLaunchToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'launchToken' => 'invalidToken',
            'agentId' => 111,
            'gameId' => 'testGameID'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/authenticate',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 3,
                'message' => 'Token Invalid',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_authenticate_invalidHeaderInvalidTokenException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testLaunchToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'launchToken' => 'testLaunchToken',
            'agentId' => 111,
            'gameId' => 'testGameID'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/authenticate',
            data: $request,
            headers: ['Authorization' => 'invalidToken']
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 3,
                'message' => 'Token Invalid',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_authenticate_InvalidAgentIDException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testLaunchToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'launchToken' => 'testLaunchToken',
            'agentId' => 468513546,
            'gameId' => 'testGameID'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/authenticate',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 31,
                'message' => "Currency does not match Agent's currency.",
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }

    public function test_authenticate_ProviderWalletErrorException_expectedData()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $wallet = new class extends TestWallet {
            public function balance(IWalletCredentials $credentials, string $playID): array
            {
                return [
                    'status_code' => 5615413
                ];
            }
        };
        app()->bind(IWallet::class, $wallet::class);

        $randomizer = new class extends Randomizer {
            public function createToken(): string
            {
                return 'testRandomToken';
            }
        };
        app()->bind(Randomizer::class, $randomizer::class);

        DB::table('hg5.players')->insert([
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hg5.playgame')->insert([
            'play_id' => 'testPlayID',
            'token' => 'testLaunchToken',
            'expired' => 'FALSE',
            'status' => 'oldGameID'
        ]);

        $request = [
            'launchToken' => 'testLaunchToken',
            'agentId' => 111,
            'gameId' => 'testGameID'
        ];

        $response = $this->post(
            uri: 'hg5/prov/GrandPriest/authenticate',
            data: $request,
            headers: [
                'Authorization' => 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtYXN0ZXJQYXJlbnRJZCI6ImFpeGFkbWluIiwic' .
                    'GFyZW50SWQiOiJhaXhpZHIyIiwiaWF0IjoxNzM2MzIyMjMyfQ.PGHyTKgnYdZKqdwqHe2fIZpuxx4aFEh0svHjskKvSJk'
            ]
        );

        $response->assertJson([
            'data' => null,
            'status' => [
                'code' => 105,
                'message' => 'Wallet service error.',
                'datetime' => '2024-01-01T00:00:00.000000000-04:00'
            ]
        ]);

        $response->assertStatus(200);
    }
}