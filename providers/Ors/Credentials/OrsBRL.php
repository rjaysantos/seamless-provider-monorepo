<?php

namespace Providers\Ors\Credentials;

use Providers\Ors\Contracts\ICredentials;

class OrsBRL implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJPUlMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTc0MDIwODUsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOGY3ZDM1ZWFlNTQxY2I1MTNhYmM0NTBjM2JlNGQyNWUiLCJzdWIiOiJBdWRTeXMifQ.Q8-7dofAubMcSJOgl8hMTHcQ0re62A_d0bK-nmKlXuv1MXsU55JfjAZKgXasB_AtN6lXboDAwKBd533ex8Y6dYnMjSC0XaP1SUlJS-038fJ0tT7px46lyQ6A6NStCWSJvAADMMyQ8PR09WC1Yc-0mEU8ZzERlJGzTRg80b0DDG7P8vHJkBCLVDYYYCcoAh9EDEWS3NAU1kCPZ3ebyG7bhoxVvbwPizeLIi67B-U84bT84lqqexhtz7aF4FdrMCylxjCm3gb1KUaV1e9hTj_QA0w5pyl3XRl8epeoDnRhXw8zQ-EO8ZpocYB456xE6HivV5mpP6PMMl7ifIcunqgDLQ';
    }

    public function getGrpcSignature(): string
    {
        return '2901dc61c18ebc7c0db3c9bab643a281';
    }

    public function getProviderCode(): string
    {
        return 'ORS';
    }

    public function getApiUrl(): string
    {
        return 'https://apollo2.all5555.com';
    }

    public function getOperatorName(): string
    {
        return 'mog052slotbrl';
    }

    public function getPublicKey(): string
    {
        return 'vAaAYEWbtHEdshR5fZGK4lDpYHGCI2DE';
    }

    public function getPrivateKey(): string
    {
        return 'a8CpWy7PH7MkrrNSgxxlKZIq3TNsCVxb';
    }

    public function getArcadeGameList(): array
    {
        return json_decode(env('ORS_ARCADE_GAMES'), true);
    }
}
