<?php

use Tests\TestCase;
use App\Contracts\V2\IWallet;
use App\Libraries\Wallet\V2\TestWallet;

class PcaHealthCheckTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app()->bind(IWallet::class, TestWallet::class);
    }

    public function test_healthCheck_apiCalled_statusCode200()
    {
        $response = $this->post('pca/prov/healthcheck');

        $response->assertJson([]);

        $response->assertStatus(200);
    }
}