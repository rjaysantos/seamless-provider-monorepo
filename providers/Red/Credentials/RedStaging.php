<?php

namespace Providers\Red\Credentials;

use Providers\Red\Contracts\ICredentials;

class RedStaging implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJSRUQiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTQ3MTU4ODcsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOWExZGI4NTIzNzYzZDZlNTNjMTgxZTU1ZjczYjc2OTIiLCJzdWIiOiJBdWRTeXMifQ.PRsMWHBq40aF8THv5f0lVfBwX-e4f2SX7QC10TEb9jrCW1g0uAtUw54lBtvM-T3NCEhoao7k9gNETYOSli7Qp_vByJxYcx10vRVvl0TuoV8hQjX0idwUzM4L5bU2ESDL35BrJtNKkLbSX_zDURiHtE951EnQG6XJL1VLEyjPPdtAkI-NylLcsI7IdlIK_WUiCYZ-DNVKaSdqXkSIpWwHS1KL8VpPgkTbX7YKiowIWc8NRZqkysY5uYR4APjE2ZXATZvRLl0pgVtLoQuIghooJ1xwth3uCUZRIwhmUVU6U76jOduaQCNT9RfFREK_S3lGgTucxCQEZ65AO8wwAFOt8A';
    }

    public function getGrpcSignature(): string
    {
        return '4e8ac084f7f62182cefdaaf5d407023d';
    }

    public function getProviderCode(): string
    {
        return 'RED';
    }

    public function getApiUrl(): string
    {
        return 'https://uat.ps9games.com';
    }

    public function getCode(): string
    {
        return 'MPO0114';
    }

    public function getToken(): string
    {
        return '3BQ9KGFtnQtno4kz12bMP4UqhVqWlWtz';
    }

    public function getPrdID(): int
    {
        return 213;
    }

    public function getSecretKey(): string
    {
        return 'MtVRWb3SzvOiF7Ll9DTcT1rMSyJIUAad';
    }
}
