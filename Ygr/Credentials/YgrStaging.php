<?php

namespace Providers\Ygr\Credentials;

use Providers\Ygr\Contracts\ICredentials;

class YgrStaging implements ICredentials
{
    public function getGrpcHost(): string
    {
        return '12.0.129.253';
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
        return 'YGR';
    }

    public function getApiUrl(): string
    {
        return 'https://tyche8wmix-service.yahutech.com';
    }

    public function getVendorID(): string
    {
        return 'AIX';
    }
}