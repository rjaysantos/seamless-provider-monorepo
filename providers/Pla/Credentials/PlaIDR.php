<?php

namespace App\GameProviders\V2\PLA\Credentials;

use App\GameProviders\V2\PLA\Contracts\ICredentials;

class PlaIDR implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJQTEEiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjM3MTE0NzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiNzkzZmZmYzBhZWZkZGI3YjMwYjFkNTUzMTgyZjFiMDAiLCJzdWIiOiJBdWRTeXMifQ.TJ_BTNHNEI0c09qCaiU-rdkkuQ3LYB-5oh-c2vaDOOpAy7rjoD4_EeggILG3xQb-koyN2mUe3ZB_51QumxqoD743oeJlG3VDc9NJgG1Ru0PQ-6z8wRpnHeEJmV_87zNQd4uAwM86H0YL0FbwReQ5FsI5oeNJi8dnNMX6I2w85k1cdO-L0jERW99qQi0juBok9kKS6DZ8jrY3ScPZBKdX3EgnFBoyTjq1dKPwdVJvwwf4R2StDAXYyGIbcR5HlKWktI6X4ITR9KaPw75LhCeczYf9Shypl1O8bJnuQPhPFCl6rXJ1UT99WVPiF14s6SmUUTT3jU4wLwlYXE0iLkKQ1g';
    }

    public function getGrpcSignature(): string
    {
        return '05f7a13c9d540b311c079bf3ae4a36d9';
    }

    public function getProviderCode(): string
    {
        return 'PLA';
    }

    public function getApiUrl(): string
    {
        return 'https://api.agmidway.com';
    }

    public function getKioskKey(): string
    {
        return '613e5d694b0a17f5d22fe3bd3b031e2494c3f23cdc3efe8b9d94e4b803979e36';
    }

    public function getKioskName(): string
    {
        return 'PLAID';
    }

    public function getServerName(): string
    {
        return 'AGCASINO';
    }

    public function getAdminKey(): string
    {
        return '5b1f3ec393c9fbd072d2e14642dfe902c596258112fac46492403b3fb24b3ce3';
    }

    public function getArcadeGameList(): array
    {
        return [
            'db1000ro',
            'ro101',
            '3cb',
            'sem',
            'ba',
            'baws',
            'bafr',
            'mobbj',
            'mbj',
            'bjsd2',
            'bjs',
            'bjbu',
            'ccro',
            'ccrobf',
            'crit',
            'cpx5k',
            'gpas_critmp_pop',
            'bjcb',
            'cheaa',
            'circ',
            'ro',
            'dbro',
            'eufro',
            'fishshr',
            'fcbj',
            'hilop',
            'huh',
            'po',
            'jbc',
            'gpas_kickitmp_pop',
            'bjll',
            'mfbro',
            'mro',
            'mpbj',
            'mpro',
            'prol',
            'pfbj_mh5',
            'rodz_g',
            'bjto_sh',
            'bjto',
            'ro_g',
            'frr_g',
            'qbjp',
            'qro',
            'presrol',
            'mro_g',
            'shsfc',
            'lwh',
            'stywro',
            'sbro',
            'supro',
            'bj65'
        ];
    }
}
