<?php

namespace Providers\Pca\Credentials;

use Providers\Pca\Contracts\ICredentials;

class PcaStaging implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQQ0EiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTEzODgsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNWJmMjRjMzQwY2ZlNDcwY2RkZDFmMGVlMTBjMGViYzkiLCJzdWIiOiJBdWRTeXMifQ.C7UljFNSF23pzlt7hecpAaRVVPQ_dxoDH7o2UdkHSZyj5tPcWfcg_5xDRD_awASw9fhV0ya_5E55LFEDmCZguqxxxAXFhR1FyFgeATJjT6S2M0iAFCcdkVljju1Sc2AL3QFeGEboTsqz9p8GfliVdcg05RmspaTEupLgV3kYn2ssEJG5wf9s9ohMtzCRaollBeEU3jLvB-D9ZJGnKilP6TtEGOqfAH4malJABSRSDkZG0WCX5fnu7_mGPyKzsQ-MBeE-DE-xrTWczjf1nD1uLnMB2zqpCOZGYj5f4xrkAOwHApW60G-a9W38MdUVb8C2fDl75XDx1KTSW0NGKgxdaQ';
    }

    public function getGrpcSignature(): string
    {
        return '6cb6422cab16487fb0bd77805bff3df7';
    }

    public function getProviderCode(): string
    {
        return 'PCA';
    }

    public function getApiUrl(): string
    {
        return 'https://api-uat.agmidway.net';
    }

    public function getKioskKey(): string
    {
        return '6e7928b51d2790e1b959fafc6a83f93d9eff411fc33384ac7faa0c8d54ad0774';
    }

    public function getKioskName(): string
    {
        return 'PCAUCN';
    }

    public function getServerName(): string
    {
        return 'AGCASTG';
    }

    public function getAdminKey(): string
    {
        return '3bd7228891fb21391c355dda69a27548044ebf2bfc7d7c3e39c3f3a08e72e4e0';
    }

    public function getCurrency(): string
    {
        return 'CNY';
    }

    public function getCountryCode(): string
    {
        return 'CN';
    }
}