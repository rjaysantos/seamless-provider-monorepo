<?php

use Tests\TestCase;
use Providers\Pca\PcaCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class PcaCredentialsTest extends TestCase
{
    public function makeCredentialSetter()
    {
        return new PcaCredentials();
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PcaStaging_expected($field)
    {
        $expected = [
            'grpcHost' => '12.0.129.253',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTEzODgsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNWJmMjRjMzQwY2ZlNDcwY2RkZDFmMGVlMTBjMGViYzkiLCJzdWIiOiJBdWRTeXMifQ.C7UljFNSF23pzlt7hecpAaRVVPQ_dxoDH7o2UdkHSZyj5tPcWfcg_5xDRD_awASw9fhV0ya_5E55LFEDmCZguqxxxAXFhR1FyFgeATJjT6S2M0iAFCcdkVljju1Sc2AL3QFeGEboTsqz9p8GfliVdcg05RmspaTEupLgV3kYn2ssEJG5wf9s9ohMtzCRaollBeEU3jLvB-D9ZJGnKilP6TtEGOqfAH4malJABSRSDkZG0WCX5fnu7_mGPyKzsQ-MBeE-DE-xrTWczjf1nD1uLnMB2zqpCOZGYj5f4xrkAOwHApW60G-a9W38MdUVb8C2fDl75XDx1KTSW0NGKgxdaQ',
            'grpcSignature' => '6cb6422cab16487fb0bd77805bff3df7',
            'providerCode' => 'PCA',
            'apiUrl' => 'https://api-uat.agmidway.net',
            'getKioskKey' => '6e7928b51d2790e1b959fafc6a83f93d9eff411fc33384ac7faa0c8d54ad0774',
            'getKioskName' => 'PCAUCN',
            'getServerName' => 'AGCASTG',
            'getAdminKey' => '3bd7228891fb21391c355dda69a27548044ebf2bfc7d7c3e39c3f3a08e72e4e0',
            'getCurrency' => 'CNY',
            'getCountryCode' => 'CN',
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'IDR');

        $this->assertSame(
            expected: $expected[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PcaIDR_expected($field)
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
            'getCurrency' => 'IDR',
            'getCountryCode' => 'ID',
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'IDR');

        $this->assertSame(
            expected: $expected[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PcaPHP_expected($field)
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
            'getCurrency' => 'PHP',
            'getCountryCode' => 'PH',
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'PHP');

        $this->assertSame(
            expected: $expected[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PcaTHB_expected($field)
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
            'getCurrency' => 'THB',
            'getCountryCode' => 'TH',
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'THB');

        $this->assertSame(
            expected: $expected[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PcaVND_expected($field)
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
            'getCurrency' => 'VND',
            'getCountryCode' => 'VN',
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'VND');

        $this->assertSame(
            expected: $expected[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PcaUSD_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE1MTEsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGE3MzVhOGYzMGU3OWU5YTNjMWMzZTJkMWEwN2JmOTYiLCJzdWIiOiJBdWRTeXMifQ.DETHEk1hDzkf_VpMg-Eh-vtQ-gKCnqgXOg_QXOfflWW7xZ7wKQhG_g6fvHg1F-8kzIe5r72Zpuy4zaTrgx1SjPyziLGvx0VhRNWiWD1bHkMLY8U54QeiMQEh-yG60hS5sCghbCRmYqfpKHUravuuFMPhuGWw0zP0u27JfJ5SE2htmy8YipZlXWfB9TjMuu50kBWb_egyS5V1Z-soV8FIqGzXUODXuT9Qj91uK3MDvrgdz79Iq4T1VECQc064_2alomRG9UNJEAXzBwbocSO1dhJ3cEw6c3EiLbUowj4EqMpSGG1gJgiJmUCWW15KJF5zB002IsQSsGb40G5blR1m_w',
            'grpcSignature' => '68a41af718ea0bf29dfe22807786623b',
            'providerCode' => 'PCA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => 'bb052bd9a863bcefe0b5571e4e95788e8dc643b51331d5525624011c260b614c',
            'getKioskName' => 'PCAUS',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '69223691b94cacefca8bd2ac2a0f2d519fe78d326a52da29d6928d7d7aa325f9',
            'getCurrency' => 'USD',
            'getCountryCode' => 'US',
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'USD');

        $this->assertSame(
            expected: $expected[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_PcaMYR_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE1MTEsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGE3MzVhOGYzMGU3OWU5YTNjMWMzZTJkMWEwN2JmOTYiLCJzdWIiOiJBdWRTeXMifQ.DETHEk1hDzkf_VpMg-Eh-vtQ-gKCnqgXOg_QXOfflWW7xZ7wKQhG_g6fvHg1F-8kzIe5r72Zpuy4zaTrgx1SjPyziLGvx0VhRNWiWD1bHkMLY8U54QeiMQEh-yG60hS5sCghbCRmYqfpKHUravuuFMPhuGWw0zP0u27JfJ5SE2htmy8YipZlXWfB9TjMuu50kBWb_egyS5V1Z-soV8FIqGzXUODXuT9Qj91uK3MDvrgdz79Iq4T1VECQc064_2alomRG9UNJEAXzBwbocSO1dhJ3cEw6c3EiLbUowj4EqMpSGG1gJgiJmUCWW15KJF5zB002IsQSsGb40G5blR1m_w',
            'grpcSignature' => '68a41af718ea0bf29dfe22807786623b',
            'providerCode' => 'PCA',
            'apiUrl' => 'https://api.agmidway.com',
            'getKioskKey' => 'fe41146776deeb7d980646773bc7950607d85ffbaf9cd98b3cbccc8cd58ba4c7',
            'getKioskName' => 'PCAMY',
            'getServerName' => 'AGCASINO',
            'getAdminKey' => '69223691b94cacefca8bd2ac2a0f2d519fe78d326a52da29d6928d7d7aa325f9',
            'getCurrency' => 'MYR',
            'getCountryCode' => 'MY',
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency(currency: 'MYR');

        $this->assertSame(
            expected: $expected[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
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
            ['getCurrency'],
            ['getCountryCode'],
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
            case 'getCurrency':
                return $credentials->getCurrency();
            case 'getCountryCode':
                return $credentials->getCountryCode();
            default:
                return null;
        }
    }
}