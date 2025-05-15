<?php

namespace Tests\Feature\HCG\Casino\Production;

use Tests\TestCase;
use App\GameProviders\V2\Hcg\HcgCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class HcgCredentialsTest extends TestCase
{
    public function makeCredentialSetter()
    {
        return new HcgCredentials();
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_HcgIDR_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJIQ0ciLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3OTM2MzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiODllNDZhOTQzYWEzZDYwMzM5YmZjNTZjZjgzODgwMGEiLCJzdWIiOiJBdWRTeXMifQ.BC5XxLvH-WY-T1wyM-0wwhUsEpZ5IFQhLg1xVYfXgKt7SRXHuilhgpdHsSuDkNafcJl0TUVpderqEAtphC_Vja7SeYjtcpD0DSBhtyVIrr95dRI1V7BVbUny4yaPWW2O4giCS1QqrLbLZbCvahyBJCRc0QovttqLR6MsAqVJEVxcpuICmaL4lq-jdIXCEGkwzR6d_IaA9JqnzmvesXtj5IhqEfF4Donf4DAAmn2gFZa-Hhqg8ROy2bmlWj2DO3kxCIV9T5X7T1Zf4Uf0XrUFBveAGU6P1bUkd-iHlSm-mLZrO3n4vP-eOIJlpIXUmSrNa7YA4ChtX0--6i0Rhx7VRw',
            'grpcSignature' => '9cc0334e38f47e719e0a0b0342d2f92a',
            'providerCode' => 'HCG',
            'apiUrl' => 'https://api.jav8889.com/gbRequest',
            'signKey' => '357d4cf555d6b4a18dd1617487bf6bad',
            'walletApiSignKey' => '1|8MojGMjQ878CFY4mBBgFNXDq7yP6GJf6XBYwfGxHa304467b',
            'encryptionKey' => 'ebfc8cc9e3b4111142049be708c3b07c',
            'appId' => 'vgF0t7Hs3b25HZwQ7J',
            'appSecret' => 'rT5twrkhziLxoyoxsiZmIUHoEHQrDYNg',
            'agentId' => '2747',
            'visualUrl' => 'https://order.jav8889.com'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('IDR');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_HcgPHP_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJIQ0ciLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3OTM2MzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiODllNDZhOTQzYWEzZDYwMzM5YmZjNTZjZjgzODgwMGEiLCJzdWIiOiJBdWRTeXMifQ.BC5XxLvH-WY-T1wyM-0wwhUsEpZ5IFQhLg1xVYfXgKt7SRXHuilhgpdHsSuDkNafcJl0TUVpderqEAtphC_Vja7SeYjtcpD0DSBhtyVIrr95dRI1V7BVbUny4yaPWW2O4giCS1QqrLbLZbCvahyBJCRc0QovttqLR6MsAqVJEVxcpuICmaL4lq-jdIXCEGkwzR6d_IaA9JqnzmvesXtj5IhqEfF4Donf4DAAmn2gFZa-Hhqg8ROy2bmlWj2DO3kxCIV9T5X7T1Zf4Uf0XrUFBveAGU6P1bUkd-iHlSm-mLZrO3n4vP-eOIJlpIXUmSrNa7YA4ChtX0--6i0Rhx7VRw',
            'grpcSignature' => '9cc0334e38f47e719e0a0b0342d2f92a',
            'providerCode' => 'HCG',
            'apiUrl' => 'https://api.jav8889.com/gbRequest',
            'signKey' => '357d4cf555d6b4a18dd1617487bf6bad',
            'walletApiSignKey' => '1|8MojGMjQ878CFY4mBBgFNXDq7yP6GJf6XBYwfGxHa304467b',
            'encryptionKey' => 'ebfc8cc9e3b4111142049be708c3b07c',
            'appId' => 'EHaBBhpymTKIHqZHp2',
            'appSecret' => 'abnkTFGII0KAp4jQXrZVNSvTfNy4DD7C',
            'agentId' => '2740',
            'visualUrl' => 'https://order.jav8889.com'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('PHP');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_HcgTHB_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJIQ0ciLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3OTM2MzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiODllNDZhOTQzYWEzZDYwMzM5YmZjNTZjZjgzODgwMGEiLCJzdWIiOiJBdWRTeXMifQ.BC5XxLvH-WY-T1wyM-0wwhUsEpZ5IFQhLg1xVYfXgKt7SRXHuilhgpdHsSuDkNafcJl0TUVpderqEAtphC_Vja7SeYjtcpD0DSBhtyVIrr95dRI1V7BVbUny4yaPWW2O4giCS1QqrLbLZbCvahyBJCRc0QovttqLR6MsAqVJEVxcpuICmaL4lq-jdIXCEGkwzR6d_IaA9JqnzmvesXtj5IhqEfF4Donf4DAAmn2gFZa-Hhqg8ROy2bmlWj2DO3kxCIV9T5X7T1Zf4Uf0XrUFBveAGU6P1bUkd-iHlSm-mLZrO3n4vP-eOIJlpIXUmSrNa7YA4ChtX0--6i0Rhx7VRw',
            'grpcSignature' => '9cc0334e38f47e719e0a0b0342d2f92a',
            'providerCode' => 'HCG',
            'apiUrl' => 'https://api.jav8889.com/gbRequest',
            'signKey' => '357d4cf555d6b4a18dd1617487bf6bad',
            'walletApiSignKey' => '1|8MojGMjQ878CFY4mBBgFNXDq7yP6GJf6XBYwfGxHa304467b',
            'encryptionKey' => 'ebfc8cc9e3b4111142049be708c3b07c',
            'appId' => 'DlDWgXpu6YgwXITZll',
            'appSecret' => 'MfkQHUtqG55pz7BQ00kizb630C286IIA',
            'agentId' => '2748',
            'visualUrl' => 'https://order.jav8889.com'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('THB');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_HcgVND_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJIQ0ciLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3OTM2MzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiODllNDZhOTQzYWEzZDYwMzM5YmZjNTZjZjgzODgwMGEiLCJzdWIiOiJBdWRTeXMifQ.BC5XxLvH-WY-T1wyM-0wwhUsEpZ5IFQhLg1xVYfXgKt7SRXHuilhgpdHsSuDkNafcJl0TUVpderqEAtphC_Vja7SeYjtcpD0DSBhtyVIrr95dRI1V7BVbUny4yaPWW2O4giCS1QqrLbLZbCvahyBJCRc0QovttqLR6MsAqVJEVxcpuICmaL4lq-jdIXCEGkwzR6d_IaA9JqnzmvesXtj5IhqEfF4Donf4DAAmn2gFZa-Hhqg8ROy2bmlWj2DO3kxCIV9T5X7T1Zf4Uf0XrUFBveAGU6P1bUkd-iHlSm-mLZrO3n4vP-eOIJlpIXUmSrNa7YA4ChtX0--6i0Rhx7VRw',
            'grpcSignature' => '9cc0334e38f47e719e0a0b0342d2f92a',
            'providerCode' => 'HCG',
            'apiUrl' => 'https://api.jav8889.com/gbRequest',
            'signKey' => '357d4cf555d6b4a18dd1617487bf6bad',
            'walletApiSignKey' => '1|8MojGMjQ878CFY4mBBgFNXDq7yP6GJf6XBYwfGxHa304467b',
            'encryptionKey' => 'ebfc8cc9e3b4111142049be708c3b07c',
            'appId' => 'loS8kRrBTC4upyBv7f',
            'appSecret' => 'XM4Ss1I6dmyuEf24G9kcLj7C4hI8b5BI',
            'agentId' => '2746',
            'visualUrl' => 'https://order.jav8889.com'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('VND');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_HcgBRL_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJIQ0ciLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3OTM2MzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiODllNDZhOTQzYWEzZDYwMzM5YmZjNTZjZjgzODgwMGEiLCJzdWIiOiJBdWRTeXMifQ.BC5XxLvH-WY-T1wyM-0wwhUsEpZ5IFQhLg1xVYfXgKt7SRXHuilhgpdHsSuDkNafcJl0TUVpderqEAtphC_Vja7SeYjtcpD0DSBhtyVIrr95dRI1V7BVbUny4yaPWW2O4giCS1QqrLbLZbCvahyBJCRc0QovttqLR6MsAqVJEVxcpuICmaL4lq-jdIXCEGkwzR6d_IaA9JqnzmvesXtj5IhqEfF4Donf4DAAmn2gFZa-Hhqg8ROy2bmlWj2DO3kxCIV9T5X7T1Zf4Uf0XrUFBveAGU6P1bUkd-iHlSm-mLZrO3n4vP-eOIJlpIXUmSrNa7YA4ChtX0--6i0Rhx7VRw',
            'grpcSignature' => '9cc0334e38f47e719e0a0b0342d2f92a',
            'providerCode' => 'HCG',
            'apiUrl' => 'https://api.jav8889.com/gbRequest',
            'signKey' => '357d4cf555d6b4a18dd1617487bf6bad',
            'walletApiSignKey' => '1|8MojGMjQ878CFY4mBBgFNXDq7yP6GJf6XBYwfGxHa304467b',
            'encryptionKey' => 'ebfc8cc9e3b4111142049be708c3b07c',
            'appId' => 'zPN240CQKlY86Rh8Zc',
            'appSecret' => '9DtqzLbb6FbgJwd2V57ao0kHCYOzN7zU',
            'agentId' => '3520',
            'visualUrl' => 'https://order.jav8889.com'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('BRL');

        $this->assertSame($expected[$field], $this->getCredentialValue($credentials, $field));
    }

    #[DataProvider('credentialParams')]
    public function test_getCredentialsByCurrency_HcgUSD_expected($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJIQ0ciLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3OTM2MzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiODllNDZhOTQzYWEzZDYwMzM5YmZjNTZjZjgzODgwMGEiLCJzdWIiOiJBdWRTeXMifQ.BC5XxLvH-WY-T1wyM-0wwhUsEpZ5IFQhLg1xVYfXgKt7SRXHuilhgpdHsSuDkNafcJl0TUVpderqEAtphC_Vja7SeYjtcpD0DSBhtyVIrr95dRI1V7BVbUny4yaPWW2O4giCS1QqrLbLZbCvahyBJCRc0QovttqLR6MsAqVJEVxcpuICmaL4lq-jdIXCEGkwzR6d_IaA9JqnzmvesXtj5IhqEfF4Donf4DAAmn2gFZa-Hhqg8ROy2bmlWj2DO3kxCIV9T5X7T1Zf4Uf0XrUFBveAGU6P1bUkd-iHlSm-mLZrO3n4vP-eOIJlpIXUmSrNa7YA4ChtX0--6i0Rhx7VRw',
            'grpcSignature' => '9cc0334e38f47e719e0a0b0342d2f92a',
            'providerCode' => 'HCG',
            'apiUrl' => 'https://api.jav8889.com/gbRequest',
            'signKey' => '357d4cf555d6b4a18dd1617487bf6bad',
            'walletApiSignKey' => '1|8MojGMjQ878CFY4mBBgFNXDq7yP6GJf6XBYwfGxHa304467b',
            'encryptionKey' => 'ebfc8cc9e3b4111142049be708c3b07c',
            'appId' => 'pVInruQLIIS0G2tv3w',
            'appSecret' => 'oKVimQRpJfZgxRBm1gdHjmmmn8wdz1T0',
            'agentId' => '2749',
            'visualUrl' => 'https://order.jav8889.com'
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
            ['signKey'],
            ['walletApiSignKey'],
            ['encryptionKey'],
            ['appId'],
            ['appSecret'],
            ['agentId'],
            ['visualUrl']
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
            case 'signKey':
                return $credentials->getSignKey();
            case 'walletApiSignKey':
                return $credentials->getWalletApiSignKey();
            case 'encryptionKey':
                return $credentials->getEncryptionKey();
            case 'appId':
                return $credentials->getAppID();
            case 'appSecret':
                return $credentials->getAppSecret();
            case 'agentId':
                return $credentials->getAgentID();
            case 'visualUrl':
                return $credentials->getVisualUrl();
            default:
                return null;
        }
    }
}
