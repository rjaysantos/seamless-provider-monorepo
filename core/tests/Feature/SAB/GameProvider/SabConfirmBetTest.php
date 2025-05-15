<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use Illuminate\Support\Facades\DB;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\TestWallet;
use App\Contracts\V2\IWalletCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SabConfirmBetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE sab.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE sab.reports RESTART IDENTITY;');
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_confirmBet_validData_confirmBetResponse()
    {
        $wallet = new class extends TestWallet {
            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'credit_after' => 1000.0,
                    'status_code' => 2100
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => '-',
                'game_code' => '1',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 0,
            'balance' => 1,
            'msg' => null
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('sab.reports', [
            'bet_id' => 'confirmBet-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);
    }

    public function test_confirmBet_userIdNotExists_playerNotFoundResponse()
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'non-existent-player',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 203,
            'msg' => 'Account Is Not Exist'
        ]);

        $response->assertStatus(200);
    }

    public function test_confirmBet_invalidKey_invalidKeyResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => 'invalid_vendor_id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'error_code' => 311,
            'message' => 'Invalid Authentication Key'
        ]);

        $response->assertStatus(200);
    }

    public function test_confirmBet_refIdNotExists_transactionNotFoundResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 504,
            'msg' => 'No Such Ticket'
        ]);

        $response->assertStatus(200);
    }

    public function test_confirmBet_flagNotWaiting_invalidTransactionStatusResponse()
    {
        DB::table('sab.players')
            ->insert([
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => '-',
                'game_code' => '1',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'running',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 309,
            'msg' => 'Invalid Transaction Status',
        ]);

        $response->assertStatus(200);
    }

    public function test_confirmBet_walletError_walletErrorResponse()
    {
        $wallet = new class extends TestWallet {
            public function wager(IWalletCredentials $credentials, string $playID, string $currency, string $transactionID, float $amount, Report $report): array
            {
                return [
                    'status_code' => 'invalid'
                ];
            }
        };

        app()->bind(IWallet::class, $wallet::class);

        DB::table('sab.players')
            ->insert([
                'play_id' => 'test-player-1',
                'username' => 'testUsername',
                'currency' => 'IDR',
                'game' => '0',
            ]);

        DB::table('sab.reports')
            ->insert([
                'bet_id' => 'wager-1-testTransactionID',
                'trx_id' => 'testTransactionID',
                'play_id' => 'testPlayID',
                'web_id' => 0,
                'currency' => 'IDR',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'bet_time' => '2021-01-01 12:00:00',
                'bet_choice' => '-',
                'game_code' => '1',
                'sports_type' => '-',
                'event' => '-',
                'match' => '-',
                'hdp' => '-',
                'odds' => 0,
                'result' => '-',
                'flag' => 'waiting',
                'status' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 901,
            'msg' => 'Database Error'
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('sab.reports', [
            'bet_id' => 'confirmBet-1-12345',
            'trx_id' => '12345',
            'play_id' => 'testPlayID',
            'web_id' => 0,
            'currency' => 'IDR',
            'bet_amount' => 1000,
            'payout_amount' => 0,
            'bet_time' => '2021-01-01 12:00:00',
            'bet_choice' => '-',
            'game_code' => 1,
            'sports_type' => '-',
            'event' => '-',
            'match' => '-',
            'hdp' => '-',
            'odds' => 0,
            'result' => '-',
            'flag' => 'running',
            'status' => '1',
            'ip_address' => '123.456.7.8'
        ]);
    }

    #[DataProvider('confirmBetParams')]
    public function test_confirmBet_incompleteRequestParameter_invalidRequestResponse($key)
    {
        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        elseif ($key === 'userId' || $key === 'updateTime' || $key === 'txns')
            unset($request['message'][$key]);
        elseif ($key === 'refId' || $key === 'txId' || $key === 'actualAmount')
            unset($request['message']['txns'][0][$key]);

        $response = $this->post('/sab/prov/confirmbet', $request);

        $response->assertJson([
            'status' => 101,
            'msg' => 'Parameter(s) Incorrect'
        ]);

        $response->assertStatus(200);
    }

    public static function confirmBetParams()
    {
        return [
            ['key'],
            ['message'],
            ['userId'],
            ['updateTime'],
            ['txns'],
            ['refId'],
            ['txId'],
            ['actualAmount'],
        ];
    }
}
