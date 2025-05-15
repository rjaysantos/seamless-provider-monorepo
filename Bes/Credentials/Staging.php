<?php

namespace Providers\Bes\Credentials;

use Providers\Bes\Contracts\ICredentials;

class Staging implements ICredentials
{
    public function getCert(): string
    {
        return 'MCo9ktIXjOiGnhqlZVdy';
    }

    public function getAgentID(): string
    {
        return 'besoftaixswuat';
    }

    public function getApiUrl(): string
    {
        return 'https://api.stag-topgame.com';
    }

    public function getNavigationApiUrl(): string
    {
        return 'http://12.0.129.164';
    }

    public function getNavigationApiBearerToken(): string
    {
        return '1|y3qi97hqjoxMTBI5OsYxn43OPyK3KHiYJIdnxo2V';
    }

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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJCRVMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MzM5MjMxMDAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZDI4ZDJmYjM2NzFmZDkzYTc0NWQ4ZjlkYTUxZWRkN2MiLCJzdWIiOiJBdWRTeXMifQ.G-uanBbr41Vq2WehXbsP7U-f_A9Z46rvDlFQerfSZfLZjo5eKzPVzvpVXIFvgyXZKNnHNETpZB4OmIf632dHbBKk44pBtbK7IEGrVz52crJH_FimXaAvM_IH6X-fgvGRFgGknoZDkqT1N4ASRUUssM8OUia-Dwb2_07-4Qhf0vnYvUiDWPoc9SCEkbgIdGLhs4lQYw5evR3BTSFYyX3cnJf7Ji0cLtq1IF4uWIHTN3WRUU5CjCPGw7-pFL08K1GyWhsqVyvjdcKfTCLwZeZCl8Uf6dzSatDn84AOqij6hE90WMaa2h_DLleHTeUEPsvAMjZkUfWkRip6xIeeTb8O5A';
    }

    public function getGrpcSignature(): string
    {
        return 'dbd719de4b36f7f093d2a9c6083f028f';
    }

    public function getProviderCode(): string
    {
        return 'BES';
    }
}
