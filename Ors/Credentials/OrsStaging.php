<?php

namespace Providers\Ors\Credentials;

use Providers\Ors\Contracts\ICredentials;

class OrsStaging implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJPUlMiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTI2NTI5OTAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMjY2NDMyODdhZWUyOTQyMDE5OThjMGVlODg0MWI3ZTgiLCJzdWIiOiJBdWRTeXMifQ.AP3kNWMyIvmM5cuI7KRjEAHRixzoY2durqt2PUfF2Y_knLIMMp6bw15hzdUqi9RgkPfhC_BY4QaKaU6CjJds4ujXVwTEoHi6CcfZ0ft8rSl3qO-Bp5Rc3OBpqH6E81T00xZ3cx2oIxOEHPIaIPHZ1jKqLeeS996XV0VnzXvZpTOo-45qaDsarfG1ruCm4Om_zUPlI1JMCxbjH_RSi9Mxtz4cqX2zHAgw22MEoJBBDY2TVfF1LEm5igcsVNirQLHkct7SKr3jWtGu1isEvoTtmtqE6mybam9cRwrCGdOG9vdjhpXOe84jrCsNqlkeSle6CVo3r27uUtvJdVk0-3-_jQ';
    }

    public function getGrpcSignature(): string
    {
        return '4164a82e46c6e428518b2f798eb317e4';
    }

    public function getProviderCode(): string
    {
        return 'ORS';
    }

    public function getApiUrl(): string
    {
        return 'http://xyz.pwqr820.com:9003';
    }

    public function getOperatorName(): string
    {
        return 'mog052testidrslot';
    }

    public function getPublicKey(): string
    {
        return 'OTpcbFdErQ86xTneBpQu7FrI8ZG0uE6x';
    }

    public function getPrivateKey(): string
    {
        return 'yKSHvRgIyexCn6AtY9s2P9q4d5JIbmsc';
    }

    public function getArcadeGameList(): array
    {
        return [131,132,151];
    }
}
