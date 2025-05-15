<?php

namespace Providers\Sab\Credentials;

use Providers\Sab\Contracts\ICredentials;

class SabStagingKCurrency implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJTQUIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjA0MjQ0OTUsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZGJmYmE0ODdiM2U2ZmNiZjBiMDVkOTc5ZmYzZDIyMDQiLCJzdWIiOiJBdWRTeXMifQ.gnRuEm6col3KZYcmelIB78EpMmvawoN_yo0gSgD-IDwocPF-kr-ble2-KwcKf57iLRfIUfN_3lu4GQ7k_VJmbTiidVTLvwyAcoOj0Z7FmOTYmQ1vEuEfAogbG2Hkws_wNSv_98Z_JbHsS4rXUDRcWyI-5DiXz4TMXtmlepAAd8A2yaOgGAuXtVLuDOtZYqceLa-BpzJbHGGybyF8UHe3UgnpKnRbxDhL3lqiLiMB0JMlqdTeZW0JQgOcD2tE1fP5t34Sti0MT3IotUdTZCm-oxKUBH_ENhcqeThz0824m94vUfzdYDYsrKR2nc-LezwfGsasauf8rltYYYwajFL9TQ';
    }

    public function getGrpcSignature(): string
    {
        return '9e7407731647cef5b4964f99a98691ad';
    }

    public function getProviderCode(): string
    {
        return 'SAB';
    }

    public function getApiUrl(): string
    {
        return 'https://p1b3tsa.bw6688.com';
    }

    public function getVendorID(): string
    {
        return '96l542m8kr';
    }

    public function getOperatorID(): string
    {
        return 'AIX';
    }

    public function getCurrency(): int
    {
        return 20;
    }

    public function getSuffix(): string
    {
        return '_test';
    }

    public function getCurrencyConversion(): int
    {
        return 1000;
    }
}
