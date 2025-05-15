<?php

use Tests\TestCase;
use App\GameProviders\V2\PCA\PcaCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class PcaCredentialsTest extends TestCase
{
    public function makeCredentialSetter()
    {
        return new PcaCredentials();
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaIDR_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE1MTEsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGE3MzVhOGYzMGU3OWU5YTNjMWMzZTJkMWEwN2JmOTYiLCJzdWIiOiJBdWRTeXMifQ.DETHEk1hDzkf_VpMg-Eh-vtQ-gKCnqgXOg_QXOfflWW7xZ7wKQhG_g6fvHg1F-8kzIe5r72Zpuy4zaTrgx1SjPyziLGvx0VhRNWiWD1bHkMLY8U54QeiMQEh-yG60hS5sCghbCRmYqfpKHUravuuFMPhuGWw0zP0u27JfJ5SE2htmy8YipZlXWfB9TjMuu50kBWb_egyS5V1Z-soV8FIqGzXUODXuT9Qj91uK3MDvrgdz79Iq4T1VECQc064_2alomRG9UNJEAXzBwbocSO1dhJ3cEw6c3EiLbUowj4EqMpSGG1gJgiJmUCWW15KJF5zB002IsQSsGb40G5blR1m_w',
            'grpcSignature' => '68a41af718ea0bf29dfe22807786623b',
            'providerCode' => 'PCA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => '2288009e93a79952d6810c4ff771bdd4ae5cee010dbb074a7dd22aba05bf92af',
            'getKioskName' => 'PCAID',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '69223691b94cacefca8bd2ac2a0f2d519fe78d326a52da29d6928d7d7aa325f9',
            'getArcadeGameList' => []
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('IDR');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaPHP_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE1MTEsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGE3MzVhOGYzMGU3OWU5YTNjMWMzZTJkMWEwN2JmOTYiLCJzdWIiOiJBdWRTeXMifQ.DETHEk1hDzkf_VpMg-Eh-vtQ-gKCnqgXOg_QXOfflWW7xZ7wKQhG_g6fvHg1F-8kzIe5r72Zpuy4zaTrgx1SjPyziLGvx0VhRNWiWD1bHkMLY8U54QeiMQEh-yG60hS5sCghbCRmYqfpKHUravuuFMPhuGWw0zP0u27JfJ5SE2htmy8YipZlXWfB9TjMuu50kBWb_egyS5V1Z-soV8FIqGzXUODXuT9Qj91uK3MDvrgdz79Iq4T1VECQc064_2alomRG9UNJEAXzBwbocSO1dhJ3cEw6c3EiLbUowj4EqMpSGG1gJgiJmUCWW15KJF5zB002IsQSsGb40G5blR1m_w',
            'grpcSignature' => '68a41af718ea0bf29dfe22807786623b',
            'providerCode' => 'PCA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => '396d3495aa7eaace9e54691acb5e9b30b65bb5fda6785c9f7ca1637701c7a465',
            'getKioskName' => 'PCAPH',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '69223691b94cacefca8bd2ac2a0f2d519fe78d326a52da29d6928d7d7aa325f9',
            'getArcadeGameList' => []
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('PHP');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaTHB_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE1MTEsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGE3MzVhOGYzMGU3OWU5YTNjMWMzZTJkMWEwN2JmOTYiLCJzdWIiOiJBdWRTeXMifQ.DETHEk1hDzkf_VpMg-Eh-vtQ-gKCnqgXOg_QXOfflWW7xZ7wKQhG_g6fvHg1F-8kzIe5r72Zpuy4zaTrgx1SjPyziLGvx0VhRNWiWD1bHkMLY8U54QeiMQEh-yG60hS5sCghbCRmYqfpKHUravuuFMPhuGWw0zP0u27JfJ5SE2htmy8YipZlXWfB9TjMuu50kBWb_egyS5V1Z-soV8FIqGzXUODXuT9Qj91uK3MDvrgdz79Iq4T1VECQc064_2alomRG9UNJEAXzBwbocSO1dhJ3cEw6c3EiLbUowj4EqMpSGG1gJgiJmUCWW15KJF5zB002IsQSsGb40G5blR1m_w',
            'grpcSignature' => '68a41af718ea0bf29dfe22807786623b',
            'providerCode' => 'PCA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => '69ed769d1d4f5e38f71df67ac3261e3ebe4f348373b8c8782832b7c8b68a9734',
            'getKioskName' => 'PCATH',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '69223691b94cacefca8bd2ac2a0f2d519fe78d326a52da29d6928d7d7aa325f9',
            'getArcadeGameList' => []
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('THB');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PlaVND_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE1MTEsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGE3MzVhOGYzMGU3OWU5YTNjMWMzZTJkMWEwN2JmOTYiLCJzdWIiOiJBdWRTeXMifQ.DETHEk1hDzkf_VpMg-Eh-vtQ-gKCnqgXOg_QXOfflWW7xZ7wKQhG_g6fvHg1F-8kzIe5r72Zpuy4zaTrgx1SjPyziLGvx0VhRNWiWD1bHkMLY8U54QeiMQEh-yG60hS5sCghbCRmYqfpKHUravuuFMPhuGWw0zP0u27JfJ5SE2htmy8YipZlXWfB9TjMuu50kBWb_egyS5V1Z-soV8FIqGzXUODXuT9Qj91uK3MDvrgdz79Iq4T1VECQc064_2alomRG9UNJEAXzBwbocSO1dhJ3cEw6c3EiLbUowj4EqMpSGG1gJgiJmUCWW15KJF5zB002IsQSsGb40G5blR1m_w',
            'grpcSignature' => '68a41af718ea0bf29dfe22807786623b',
            'providerCode' => 'PCA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => 'd34eed85085e4bb2aff689cebb3e35fa79a779b56055705ef171474c18996553',
            'getKioskName' => 'PCAVN',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '69223691b94cacefca8bd2ac2a0f2d519fe78d326a52da29d6928d7d7aa325f9',
            'getArcadeGameList' => []
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('VND');

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
            ['getKioskKey'],
            ['getKioskName'],
            ['getServerName'],
            ['getAdminKey'],
            ['getArcadeGameList']
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
            case 'getKioskKey':
                return $credentials->getKioskKey();
            case 'getKioskName':
                return $credentials->getKioskName();
            case 'getServerName':
                return $credentials->getServerName();
            case 'getAdminKey':
                return $credentials->getAdminKey();
            case 'getArcadeGameList':
                return $credentials->getArcadeGameList();
            default:
                return null;
        }
    }
}