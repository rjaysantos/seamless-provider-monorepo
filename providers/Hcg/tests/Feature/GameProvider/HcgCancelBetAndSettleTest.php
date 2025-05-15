<?php

use Tests\TestCase;
use Providers\Hcg\HcgEncryption;
use Providers\Hcg\HcgCredentials;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;

class HcgCancelBetAndSettleTest extends TestCase
{
    protected function tearDown(): void
    {
        parent::setUp();
        DB::statement('TRUNCATE TABLE hcg.players RESTART IDENTITY;');
        DB::statement('TRUNCATE TABLE hcg.reports RESTART IDENTITY;');
    }

    private function createSignature($payload, $currency): string
    {
        $credentialsLib = new HcgCredentials;
        $credentials = $credentialsLib->getCredentialsByCurrency(currency: $currency);
        $encryptionLib = new HcgEncryption;

        return $encryptionLib->createSignature(credentials: $credentials, data: $payload);
    }

    #[DataProvider('cancelBetAndSettleParams')]
    public function test_cancelBetAndSettle_invalidRequest_expectedData($unset)
    {
        $payload =  [
            'action' => 3,
            'uid' => 'playID',
            'orderNo' => 'transactionID',
        ];

        unset($payload[$unset]);

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

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
            'orderNo' => 'transactionID',
            'sign' => 'invalid signature'
        ];

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
            'orderNo' => 'transactionID',
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '9999',
            'message' => 'Action parameter error'
        ]);

        $response->assertStatus(200);
    }

    public function test_cancelBetAndSettle_cannotCancel_expectedData()
    {
        DB::table('hcg.players')->insert([
            'play_id' => 'playID',
            'username' => 'testUsername',
            'currency' => 'IDR'
        ]);

        DB::table('hcg.reports')->insert([
            'trx_id' => '0-transactionID',
            'bet_amount' => 200,
            'win_amount' => 400,
            'created_at' => '2025-01-01 00:00:00',
            'updated_at' => '2025-01-01 00:00:00'
        ]);

        $payload =  [
            'action' => 3,
            'uid' => 'playID',
            'orderNo' => 'transactionID',
        ];

        $payload['sign'] = $this->createSignature(payload: $payload, currency: 'IDR');;

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '105',
            'err_text' => 'Cannot cancel, transaction settled'
        ]);

        $response->assertStatus(200);
    }
}