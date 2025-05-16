<?php

namespace Providers\Jdb\Credentials;

use Providers\Jdb\Contracts\ICredentials;

class JdbBRL implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJKREIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjQwNTM2MDMsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMWRlMWFhZDNkOGRlMjVkYjFiODU5ZDU5NjcyOGE0YTciLCJzdWIiOiJBdWRTeXMifQ.EaJhqSiocxaAqbdujg-pFHW4oc3Pkv0f2vTrnI3X3i2SvPw6c9yMwJ4gwcrozRaz-sRB0crrnSBnqJuay5_BDoXarqU9ZAD2zQotdAOTgjZv-5b9BYFe69vMVqUSazSSRg5HHpgCvuhCC4HArzTZ7f86rqIv3tZZKJMlYHrvNQ8BSli8SiRB2xmxq5VKhkjo4QMPhfPE9HcCzq2pxnkImxwfgKEzPutEvDSIBS0sRD6wnDmKuE5IjS5H-tUQnwvV1PXme7duc9ABEwq1nZ-JtbccIvmpGEFdcv0fkrXmFOl6J5JLMzs1BzxD_X-vHPhxk_6nxkgZrqZ1x820egdy1A';
    }

    public function getGrpcSignature(): string
    {
        return '8700ccb74ca3dd6a07c57e200e805010';
    }

    public function getProviderCode(): string
    {
        return 'JDB';
    }

    public function getKey(): string
    {
        return 'aeeb06d4f766d0ed';
    }

    public function getIV(): string
    {
        return 'c8073ae48c2a5acc';
    }

    public function getDC(): string
    {
        return 'COLS';
    }

    public function getApiUrl(): string
    {
        return 'http://api.jdb1688.net';
    }

    public function getParent(): string
    {
        return 'colsbrlagaix';
    }
}
