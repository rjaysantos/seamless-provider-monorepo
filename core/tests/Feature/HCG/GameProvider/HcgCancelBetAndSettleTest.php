<?php

use Tests\TestCase;
use App\Models\HcgPlayer;
use App\Contracts\IWallet;
use Illuminate\Support\Carbon;
use App\Contracts\IWalletFactory;
use App\GameProviders\Hcg\HcgCredentials;
use App\GameProviders\Hcg\HcgEncryption;
use App\Models\HcgReport;
use Illuminate\Support\Facades\DB;

class HcgCancelBetAndSettleTest extends TestCase
{
    protected function tearDown(): void
    {
        DB::statement('TRUNCATE TABLE hcg.reports RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hcg.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hcg.playgame RESTART IDENTITY;');
        parent::tearDown();
    }

    public function createSignature($payload, $currency)
    {
        $credentialsLib = new HcgCredentials();
        $credentials = $credentialsLib->getCredentialsByCurrency($currency);
        $encryptionLib = new HcgEncryption($credentials);

        return $encryptionLib->createSignature($payload);
    }

    /**
     * @dataProvider cancelBetAndSettleParams
     */
    public function test_cancelBetAndSettle_invalidRequest_expectedData($unset)
    {
        $payload =  [
            'action' => 3,
            'uid' => 'playID',
            'orderNo' => "transactionID",
        ];

        unset($payload[$unset]);

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        if ($unset == 'sign')
            unset($payload[$unset]);

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Validation error'
        ]);

        $response->assertStatus(200);
    }

    public static function cancelBetAndSettleParams()
    {
        return [
            ['action'],
            ['uid'],
            ['orderNo'],
            ['sign']
        ];
    }

    public function test_cancelBetAndSettle_invalidSignature_expectedData()
    {
        $payload =  [
            'action' => 3,
            'uid' => 'playID',
            'orderNo' => "transactionID",
        ];

        $payload['sign'] = 'invalid signature';

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '207',
            'message' => 'Sign error'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBetAndSettle_invalidAction_expectedData()
    {
        $payload =  [
            'action' => 999,
            'uid' => 'playID',
            'orderNo' => "transactionID",
        ];

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Action parameter error'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBetAndSettle_cannotCancel_expectedData()
    {
        HcgPlayer::factory()->create([
            'play_id' => 'playID',
            'currency' => 'IDR'
        ]);

        HcgReport::factory()->create([
            'trx_id' => '0-transactionID',
            'bet_amount' => 200,
            'win_amount' => 400,
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s')
        ]);

        $payload =  [
            'action' => 3,
            'uid' => 'playID',
            'orderNo' => "transactionID",
        ];

        $signature = $this->createSignature($payload, 'IDR');

        $payload['sign'] = $signature;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '105',
            'err_text' => 'Cannot cancel, transaction settled'
        ]);

        $response->assertStatus(200);
    }
}
