<?php

namespace Providers\Gs5\Credentials;

use Providers\Gs5\Contracts\ICredentials;

class Gs5Staging implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJHUzUiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3NDUyMDA0MzcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMWQ5MzBkNTUxYzI0MGU0YjM3NGUzYTc5ZmExMTNjMTMiLCJzdWIiOiJBdWRTeXMifQ.s6QCZ_mZj_9VgqFflgDwlGV37xkh7rXF_ZSM3_rkoHQjX2J94eWx1E5aqHvVb0GonglMI3N5SPJgCauw3_3EATTYbPHPlbIABHpy6Ropjs0SarSW-BoW9g9R3zXIQz8A9RNBADi98cNO-JZc6-rs7GAJCFmQfoGjtsqQm-ZLAs7P6Wn0W8McS-J8gPXi5e9HbEpnpMTUt3hqCeHVgkxTpxk5u8p4g32N0tuu1YEYpCAySSFipEMgcG2m3xyk7p1gPJZuZc8Ej3WXtIdgA-q3MlXvVlQyER321P27z8BiyXY6oCy851-kfrd68iaGE8FareaeKPjXMJhDCFK2eS4xIQ';
    }

    public function getGrpcSignature(): string
    {
        return '0b71125d6fe90f1a6e94e0ac02cda1ca';
    }

    public function getProviderCode(): string
    {
        return 'GS5';
    }

    public function getApiUrl(): string
    {
        return 'https://stage-api.5gg.win';
    }

    public function getHostID(): string
    {
        return '81f89497d43f2eac684cb226f879c26c';
    }

    public function getToken(): string
    {
        return 'f13AeA34067CfBf0163513d1fE1Ac803876B6F98e6d7640C776F2227576D37f6';
    }
}
