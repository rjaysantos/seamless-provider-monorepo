<?php

use Tests\TestCase;
use Providers\Bes\BesCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class BesCredentialsTest extends TestCase
{
    private function makeCredentialSetter(): BesCredentials
    {
        return new BesCredentials();
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_BesIDR_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJCRVMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3NDA2MzEyODgsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZDRiZWUyMGUyODUzODk3MDFhZjE0ZGNjNTBhNDVhN2QiLCJzdWIiOiJBdWRTeXMifQ.FMSIiEb5u6DAJXkMTZcA2a9Tad5ZeKAvndg2uHtvYLAZyoJDWI99mRmjvtrS_s_m265TQoTJn2pCj7wIR7-jUYjHAP3S7oD_G4PHEG7SOhtpiTog3aGaXA0RDoEWiR4IB5YZEFBJajmYsGj3OfRQ5iOf2pQ8YwRhxqyqHxF04XoWZsEmM11vZIzsP4X2jhjaYenC20suhKl3C4bcVA2llFQWCClxaIYh_EvuDHlY77xONGnbLrIRhzF6Y3j6PbbOttYOk1g3MTU3Ors7L4tr-Z5VWUlSc0DcR1pvjWaBU02S-MdL8LIUalpE7_vM5LXVTk8iJvKe7WCdDHebseJy3g',
            'grpcSignature' => '4dcc3dc3a08fba14896c9e0fdeb7df9d',
            'providerCode' => 'BES',
            'cert' => 'cEWlKMP35iSyrRaOTnuj',
            'agentID' => 'besoftaix',
            'apiUrl' => 'https://api.prod-topgame.com'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'IDR');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    public static function credentialParameters()
    {
        return [
            ['grpcHost'],
            ['grpcPort'],
            ['grpcToken'],
            ['grpcSignature'],
            ['providerCode'],
            ['cert'],
            ['agentID'],
            ['apiUrl']
        ];
    }

    public function getCredentialValue($credentials, $field)
    {
        return match ($field) {
            'grpcHost' => $credentials->getGrpcHost(),
            'grpcPort' => $credentials->getGrpcPort(),
            'grpcToken' => $credentials->getGrpcToken(),
            'grpcSignature' => $credentials->getGrpcSignature(),
            'providerCode' => $credentials->getProviderCode(),
            'cert' => $credentials->getCert(),
            'agentID' => $credentials->getAgentID(),
            'apiUrl' => $credentials->getApiUrl(),
            default => null,
        };
    }
}