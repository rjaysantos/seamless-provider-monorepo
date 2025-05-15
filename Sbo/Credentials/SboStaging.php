<?php

namespace Providers\Sbo\Credentials;

use Providers\Sbo\Contracts\ICredentials;

class SboStaging implements ICredentials
{
    public function getGrpcHost(): string
    {
        return 'mcs-wallet-stg-a465ab3678a45b68.elb.ap-southeast-1.amazonaws.com';
    }

    public function getGrpcPort(): string
    {
        return '3939';
    }

    public function getGrpcToken(): string
    {
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJZR1IiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MzUxMjU2MDcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiYjNhZGFlOTM3MjA3NzdlMzMwZDYxOGNiMzU5YmY3YWIiLCJzdWIiOiJBdWRTeXMifQ.h1CjLDtiAqU277G1uDy9Gw2TpSQ-m-bIjQyslB8x6h7q-wc0Y9iKZJJCySclp4DUEEReL9Bp3q-6YlOvPGqZ6_sglNj0-fu4j7ILre5niKPKxhCiQgYaEqLI3I5wrkbZ99gpf-mM-DG5IDfeSMHSvsaeeHlaTVMXkzemJ0OowY3c1XkL4QSkQ3b4N-CWOXFqZCbQD5zl8SBpu8ludwxrFd1VsCoKTMNOKhJ0pyPrwrWPAXIgW7JzK2YlrAnxkJvU7hbCkvIBcDMmWs1Ax7OZV4u84a7gpx5I7dYJFXlvtVdg-b30l2G9O3L8FHvCiGtpnp-jgafhujpNciVEad0x5Q';
    }

    public function getGrpcSignature(): string
    {
        return 'b020cab53d8aee4b8f744c7bc894f4d8';
    }

    public function getProviderCode(): string
    {
        return 'SBO';
    }

    public function getCompanyKey(): string
    {
        return 'F34A561C731843F5A0AD5FA589060FBB';
    }

    public function getServerID(): string
    {
        return 'GA-staging';
    }

    public function getAgent(): string
    {
        return 'test_agent_ido_01';
    }

    public function getApiUrl(): string
    {
        return 'https://ex-api-demo-yy.568win.com';
    }
}
