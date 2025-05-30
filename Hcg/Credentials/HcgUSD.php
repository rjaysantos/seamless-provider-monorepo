<?php

namespace Providers\Hcg\Credentials;

use Providers\Hcg\Contracts\ICredentials;

class HcgUSD implements ICredentials
{
    public function getGrpcHost(): string
    {
        return '10.8.134.48';
    }

    public function getGrpcPort(): string
    {
        return '3939';
    }

    public function getGrpcToken(): string
    {
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJIQ0ciLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3OTM2MzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiODllNDZhOTQzYWEzZDYwMzM5YmZjNTZjZjgzODgwMGEiLCJzdWIiOiJBdWRTeXMifQ.BC5XxLvH-WY-T1wyM-0wwhUsEpZ5IFQhLg1xVYfXgKt7SRXHuilhgpdHsSuDkNafcJl0TUVpderqEAtphC_Vja7SeYjtcpD0DSBhtyVIrr95dRI1V7BVbUny4yaPWW2O4giCS1QqrLbLZbCvahyBJCRc0QovttqLR6MsAqVJEVxcpuICmaL4lq-jdIXCEGkwzR6d_IaA9JqnzmvesXtj5IhqEfF4Donf4DAAmn2gFZa-Hhqg8ROy2bmlWj2DO3kxCIV9T5X7T1Zf4Uf0XrUFBveAGU6P1bUkd-iHlSm-mLZrO3n4vP-eOIJlpIXUmSrNa7YA4ChtX0--6i0Rhx7VRw';
    }

    public function getGrpcSignature(): string
    {
        return '9cc0334e38f47e719e0a0b0342d2f92a';
    }

    public function getProviderCode(): string
    {
        return 'HCG';
    }

    public function getApiUrl(): string
    {
        return 'https://api.jav8889.com/gbRequest';
    }
    public function getSignKey(): string
    {
        return '357d4cf555d6b4a18dd1617487bf6bad';
    }

    public function getWalletApiSignKey(): string
    {
        return '1|8MojGMjQ878CFY4mBBgFNXDq7yP6GJf6XBYwfGxHa304467b';
    }

    public function getEncryptionKey(): string
    {
        return 'ebfc8cc9e3b4111142049be708c3b07c';
    }

    public function getAppID(): string
    {
        return 'pVInruQLIIS0G2tv3w';
    }

    public function getAppSecret(): string
    {
        return 'oKVimQRpJfZgxRBm1gdHjmmmn8wdz1T0';
    }

    public function getAgentID(): string
    {
        return '2749';
    }

    public function getVisualUrl(): string
    {
        return 'https://order.jav8889.com';
    }

    public function getCurrencyConversion(): int
    {
        return 1;
    }

    public function getTransactionIDPrefix(): string
    {
        return '1';
    }
}
