<?php

namespace Providers\Bes\Credentials;

use Providers\Bes\Contracts\ICredentials;

class BesIDR implements ICredentials
{
    public function getCert(): string
    {
        return 'cEWlKMP35iSyrRaOTnuj';
    }

    public function getAgentID(): string
    {
        return 'besoftaix';
    }

    public function getApiUrl(): string
    {
        return 'https://api.prod-topgame.com';
    }

    public function getNavigationApiUrl(): string
    {
        return 'http://internal-mcs-nav-ref-mapping-prod-lb-1763179160.ap-southeast-1.elb.amazonaws.com';
    }

    public function getNavigationApiBearerToken(): string
    {
        return '1|QPr0X7eqOJFUzMe5PEFbWJSYVTJUS0TlVnbpm3O4';
    }

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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJCRVMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3NDA2MzEyODgsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZDRiZWUyMGUyODUzODk3MDFhZjE0ZGNjNTBhNDVhN2QiLCJzdWIiOiJBdWRTeXMifQ.FMSIiEb5u6DAJXkMTZcA2a9Tad5ZeKAvndg2uHtvYLAZyoJDWI99mRmjvtrS_s_m265TQoTJn2pCj7wIR7-jUYjHAP3S7oD_G4PHEG7SOhtpiTog3aGaXA0RDoEWiR4IB5YZEFBJajmYsGj3OfRQ5iOf2pQ8YwRhxqyqHxF04XoWZsEmM11vZIzsP4X2jhjaYenC20suhKl3C4bcVA2llFQWCClxaIYh_EvuDHlY77xONGnbLrIRhzF6Y3j6PbbOttYOk1g3MTU3Ors7L4tr-Z5VWUlSc0DcR1pvjWaBU02S-MdL8LIUalpE7_vM5LXVTk8iJvKe7WCdDHebseJy3g';
    }

    public function getGrpcSignature(): string
    {
        return '4dcc3dc3a08fba14896c9e0fdeb7df9d';
    }

    public function getProviderCode(): string
    {
        return 'BES';
    }
}
