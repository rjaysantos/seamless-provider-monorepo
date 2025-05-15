<?php

use Tests\TestCase;
use Providers\Hcg\HcgEncryption;
use Providers\Hcg\HcgCredentials;

class HcgGameOfflineNotificationTest extends TestCase
{
    public function test_gameOfflineNotification_apiCalled_expectedData()
    {
        $payload = [
            'action' => 4,
        ];

        $credentialsLib = new HcgCredentials;
        $credentials = $credentialsLib->getCredentialsByCurrency(currency: 'IDR');
        $encryptionLib = new HcgEncryption;

        $payload['sign'] = $encryptionLib->createSignature(credentials: $credentials, data: $payload);

        $response = $this->post('/hcg/prov/IDR', $payload);

        $response->assertJson([
            'code' => '0'
        ]);

        $response->assertStatus(200);
    }
}