<?php

namespace Providers\Sbo\Credentials;

use Providers\Sbo\Contracts\ICredentials;

class SboIDR implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJTQk8iLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTkyOTM4MjEsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMTg2Mjc4ODRmY2ZkMTNkYTk4NTM0Njk4YzUyNjc1YzciLCJzdWIiOiJBdWRTeXMifQ.tykCWXArjJHDisGOqG-UHeZBOfrUneG5zwzZOTL5DsxNHREUQQcdeCZoxHVBZuyH8EJ3l3W6QeaKxJG-HN9DXnn4zLrqDm8f2T_t6R_WlTlc_MuUi0UGHVZXRS7ljnaEh5E40lNYm481vX5xgf3c1bieI_QERTrALV3ydg7oc-OGKimAJHz9265GlE054lwqjh2wqjCN4NrKqbECzkCLFpch0YL-wEjfJjyF1zJuPx-1oWk0WddQczh7ht1Jph-nusPfLSjeUWPk3N1U_nI64SchXH0viRCGPlnxNM54WAI1wmweyf_-4sNIEIpNZ1fkUmYzbirMsIygIui-X3_jqQ';
    }

    public function getGrpcSignature(): string
    {
        return '1a2b2a541f9c33ce6932d43bc6a80bc7';
    }

    public function getProviderCode(): string
    {
        return 'SBO';
    }

    public function getApiUrl(): string
    {
        return 'https://ex-api-yy.xxttgg.com';
    }

    public function getCompanyKey(): string
    {
        return '7DC996ABC2E642339147E5F776A3AE85';
    }

    public function getAgent(): string
    {
        return 'AIXSWIDR_';
    }

    public function getServerID(): string
    {
        return 'GA-production-01';
    }
}
