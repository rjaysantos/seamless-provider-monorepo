<?php

namespace Providers\Sab\Credentials;

use Providers\Sab\Contracts\ICredentials;

class SabBRL implements ICredentials
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
        return 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJTQUIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjgyODkzMjksImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOTNiOGQ4MDYyNmZlNWVmNWZjZjYwOWM4MzUxYWRiNmIiLCJzdWIiOiJBdWRTeXMifQ.xNiFKSRciHV7bF7Fi4VdiswLV8INKVi-uc_RgmAeZOCCyQ-AC44Es855t6-qHPL2iQ-c8oevGF9wqVxPjjF8v-QCzE0alTL0blID8HvwMvNp6TBR-cyGgOdeDfFBw3NWKezORMm71qstPxgvHN0KWrLbPFecfRnbn56rsMENq1fa-DPgA8eYupS8jakUEL_9ZHwsTMnlgzCEDLM8mVCmX8RnW1ZmsTzmJ0D5ryXX4EHlbdLNw2er4zbDshbTc7_Vq8Rw8XJLniriTBR5uWMOdmp3WoR-62p182ibS9sNTT0PSjMcIoAb2NJHl6LFkTK098uUPlwUUuqdZH_545sKHg';
    }

    public function getGrpcSignature(): string
    {
        return '221c15eb58e250054eacb7d5e87f5d5a';
    }

    public function getProviderCode(): string
    {
        return 'SAB';
    }

    public function getApiUrl(): string
    {
        return 'https://p1b3api.bw6688.com';
    }

    public function getVendorID(): string
    {
        return 'oe880dd8en';
    }

    public function getOperatorID(): string
    {
        return 'AIXSW';
    }

    public function getCurrency(): int
    {
        return 82;
    }

    public function getSuffix(): string
    {
        return '';
    }

    public function getCurrencyConversion(): int
    {
        return 1;
    }
}
