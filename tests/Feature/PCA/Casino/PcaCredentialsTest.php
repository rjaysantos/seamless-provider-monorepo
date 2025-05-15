<?php

use Tests\TestCase;
use App\GameProviders\Pca\PcaCredentials;

class PcaCredentialsTest extends TestCase
{
    public function makeCredentialSetter()
    {
        return new PcaCredentials();
    }

    /**
     * @dataProvider credentialParams
     */
    public function test_getCredentialsByCurrency_pcaProduction_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE1MTEsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGE3MzVhOGYzMGU3OWU5YTNjMWMzZTJkMWEwN2JmOTYiLCJzdWIiOiJBdWRTeXMifQ.DETHEk1hDzkf_VpMg-Eh-vtQ-gKCnqgXOg_QXOfflWW7xZ7wKQhG_g6fvHg1F-8kzIe5r72Zpuy4zaTrgx1SjPyziLGvx0VhRNWiWD1bHkMLY8U54QeiMQEh-yG60hS5sCghbCRmYqfpKHUravuuFMPhuGWw0zP0u27JfJ5SE2htmy8YipZlXWfB9TjMuu50kBWb_egyS5V1Z-soV8FIqGzXUODXuT9Qj91uK3MDvrgdz79Iq4T1VECQc064_2alomRG9UNJEAXzBwbocSO1dhJ3cEw6c3EiLbUowj4EqMpSGG1gJgiJmUCWW15KJF5zB002IsQSsGb40G5blR1m_w',
            'grpcSignature' => '68a41af718ea0bf29dfe22807786623b',
            'providerCode' => 'PCA',
            'apiUrl' => 'https://api.torrospin.com/',
            'apiKey' => 'd067982f112cb8a544edf70743519c0e92149aaad037c6409719665d5117a104',
            'secretKey' => 'a2371597187ca40d800a13fa793b5df06b31c169f78bc92cb51c474d2d92063f',
            'isLiveGame' => true,
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('USD');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    public static function credentialParams()
    {
        return [
            ['grpcHost'],
            ['grpcPort'],
            ['grpcToken'],
            ['grpcSignature'],
            ['providerCode'],
            ['apiUrl'],
            ['apiKey'],
            ['secretKey'],
            ['isLiveGame']
        ];
    }

    public function getCredentialValue($credentials, $field)
    {
        switch ($field) {
            case 'grpcHost':
                return $credentials->getGrpcHost();
            case 'grpcPort':
                return $credentials->getGrpcPort();
            case 'grpcToken':
                return $credentials->getGrpcToken();
            case 'grpcSignature':
                return $credentials->getGrpcSignature();
            case 'providerCode':
                return $credentials->getProviderCode();
            case 'apiUrl':
                return $credentials->getApiUrl();
            case 'apiKey':
                return $credentials->getApiKey();
            case 'secretKey':
                return $credentials->getSecretKey();
            case 'isLiveGame':
                return $credentials->isLiveGame();
            default:
                return null;
        }
    }
}
