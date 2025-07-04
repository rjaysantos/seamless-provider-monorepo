<?php

namespace Providers\Ygr\Credentials;

use Providers\Ygr\Contracts\ICredentials;

class YgrProduction implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJZR1IiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3NDIxNzY3NjQsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMTc4Y2YwMjg0ZDExMmUyOTU1NDdkZGQ1NDY0OWRmNDQiLCJzdWIiOiJBdWRTeXMifQ.bMBSjCyqhpJmlH1WKxGzY3twn_VnqH3td5bDgvO4WsDuh9Pod_mHYnH728BkCXDcbkE96BTnMIYJ25pf7ksMSdnqEr-RbqopfurpJafMiIczHVV5bzFzNaHlLVtcaFvu4c3tg54nm3LqsaHwc7kRL4gbknS5jOnm6xL88nEWUu6x6V50Zjvay1Xi6DXfdrRFJJxSozhvxcgYBudY300Wc1kA8jxniLT0-tpbHbhUhf7UfYiZHMpyXXRLMujNAzqrFVugE-xqBe3no1dswTeSB9aOotoWLf3d0A2ZFANayizE6ql9HXtOuuiTXAmIBrMfCDv-r7tiroaSeT1HJ6tdtg';
    }

    public function getGrpcSignature(): string
    {
        return 'cc135a96615e4eee45855a1e614be7e6';
    }

    public function getProviderCode(): string
    {
        return 'YGR';
    }

    public function getApiUrl(): string
    {
        return 'https://tyche8w-service.yahutech.com';
    }

    public function getVendorID(): string
    {
        return 'AIX';
    }

    public function getArcadeGameList(): array
    {
        return [
            '047',
            '046',
            '037'
        ];
    }

    public function getFishGameList(): array
    {
        return [
            '070',
            '068',
            '060',
            '052',
            '030',
            '020',
            '018',
            '017',
            '012'
        ];
    }
}
