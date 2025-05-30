<?php

namespace Providers\Red\Credentials;

use Providers\Red\Contracts\ICredentials;

class RedUSD implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJSRUQiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTUxNjM2NDAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZWY3OTBhZTkwY2JlNzJmMzI1MjVmM2IxMzQ5YzE1YjAiLCJzdWIiOiJBdWRTeXMifQ.hA_sxFutlRj8Y4PG_6K3-lC3ObOUaI_7x_H7NRj8Diw8kQjL3k0lboU4e074oiT_uHiYP1i_S54XwFm-eGr8HZNtlZX1Z7e3gsgRYxe0EsHR60OPZOsdY3kn3UWw452--4miBJJVc0q5hMckJ5REjT2-IrJgNAunODFRTYYpsoxS5Q_KiE8Lg8HinDiq8rrsKAVoPXs_1fCrt6VyIP8HLiZs-bPtJRPIcXQoAF6VJTRLyDBDwtlp3wAucJ1XPnGYYUsIrwMSFE7u1N-FBJXyz-vAEdMwSkFXC0AAnDxe-SYylTLRNatc7K3TdiCJ29-rD4xB5FUZZ0H9vkJFwe39aA';
    }

    public function getGrpcSignature(): string
    {
        return 'fc681d6722a30e2533226eeb580047a2';
    }

    public function getProviderCode(): string
    {
        return 'RED';
    }

    public function getApiUrl(): string
    {
        return 'https://ps9games.com';
    }

    public function getCode(): string
    {
        return 'EQX0136';
    }

    public function getToken(): string
    {
        return 'WR9ZYDDJjOY1vEImqCmT2W7y4HDNBfiH';
    }

    public function getPrdID(): int
    {
        return 213;
    }

    public function getSecretKey(): string
    {
        return 'hnW7Yti2NGEo7cawCyWWF7ilzcLZjmyk';
    }
}
