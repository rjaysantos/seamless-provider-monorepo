<?php

use Tests\TestCase;
use App\GameProviders\V2\Sab\SabCredentials;
use PHPUnit\Framework\Attributes\DataProvider;

class SabCredentialsTest extends TestCase
{
    private function makeCredentialSetter(): SabCredentials
    {
        return new SabCredentials();
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_SabIDRK_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJTQUIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjgyODkzMjksImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOTNiOGQ4MDYyNmZlNWVmNWZjZjYwOWM4MzUxYWRiNmIiLCJzdWIiOiJBdWRTeXMifQ.xNiFKSRciHV7bF7Fi4VdiswLV8INKVi-uc_RgmAeZOCCyQ-AC44Es855t6-qHPL2iQ-c8oevGF9wqVxPjjF8v-QCzE0alTL0blID8HvwMvNp6TBR-cyGgOdeDfFBw3NWKezORMm71qstPxgvHN0KWrLbPFecfRnbn56rsMENq1fa-DPgA8eYupS8jakUEL_9ZHwsTMnlgzCEDLM8mVCmX8RnW1ZmsTzmJ0D5ryXX4EHlbdLNw2er4zbDshbTc7_Vq8Rw8XJLniriTBR5uWMOdmp3WoR-62p182ibS9sNTT0PSjMcIoAb2NJHl6LFkTK098uUPlwUUuqdZH_545sKHg',
            'grpcSignature' => '221c15eb58e250054eacb7d5e87f5d5a',
            'providerCode' => 'SAB',
            'apiUrl' => 'https://p1b3api.bw6688.com',
            'vendorID' => 'oe880dd8en',
            'operatorID' => 'AIXSW',
            'currency' => 15,
            'suffix' => '',
            'currencyConversion' => 1000.0
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('IDR');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_SabTHB_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJTQUIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjgyODkzMjksImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOTNiOGQ4MDYyNmZlNWVmNWZjZjYwOWM4MzUxYWRiNmIiLCJzdWIiOiJBdWRTeXMifQ.xNiFKSRciHV7bF7Fi4VdiswLV8INKVi-uc_RgmAeZOCCyQ-AC44Es855t6-qHPL2iQ-c8oevGF9wqVxPjjF8v-QCzE0alTL0blID8HvwMvNp6TBR-cyGgOdeDfFBw3NWKezORMm71qstPxgvHN0KWrLbPFecfRnbn56rsMENq1fa-DPgA8eYupS8jakUEL_9ZHwsTMnlgzCEDLM8mVCmX8RnW1ZmsTzmJ0D5ryXX4EHlbdLNw2er4zbDshbTc7_Vq8Rw8XJLniriTBR5uWMOdmp3WoR-62p182ibS9sNTT0PSjMcIoAb2NJHl6LFkTK098uUPlwUUuqdZH_545sKHg',
            'grpcSignature' => '221c15eb58e250054eacb7d5e87f5d5a',
            'providerCode' => 'SAB',
            'apiUrl' => 'https://p1b3api.bw6688.com',
            'vendorID' => 'oe880dd8en',
            'operatorID' => 'AIXSW',
            'currency' => 4,
            'suffix' => '',
            'currencyConversion' => 1.0
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('THB');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_SabVNDK_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJTQUIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjgyODkzMjksImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOTNiOGQ4MDYyNmZlNWVmNWZjZjYwOWM4MzUxYWRiNmIiLCJzdWIiOiJBdWRTeXMifQ.xNiFKSRciHV7bF7Fi4VdiswLV8INKVi-uc_RgmAeZOCCyQ-AC44Es855t6-qHPL2iQ-c8oevGF9wqVxPjjF8v-QCzE0alTL0blID8HvwMvNp6TBR-cyGgOdeDfFBw3NWKezORMm71qstPxgvHN0KWrLbPFecfRnbn56rsMENq1fa-DPgA8eYupS8jakUEL_9ZHwsTMnlgzCEDLM8mVCmX8RnW1ZmsTzmJ0D5ryXX4EHlbdLNw2er4zbDshbTc7_Vq8Rw8XJLniriTBR5uWMOdmp3WoR-62p182ibS9sNTT0PSjMcIoAb2NJHl6LFkTK098uUPlwUUuqdZH_545sKHg',
            'grpcSignature' => '221c15eb58e250054eacb7d5e87f5d5a',
            'providerCode' => 'SAB',
            'apiUrl' => 'https://p1b3api.bw6688.com',
            'vendorID' => 'oe880dd8en',
            'operatorID' => 'AIXSW',
            'currency' => 51,
            'suffix' => '',
            'currencyConversion' => 1000.0
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('VND');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_SabBRL_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJTQUIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjgyODkzMjksImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOTNiOGQ4MDYyNmZlNWVmNWZjZjYwOWM4MzUxYWRiNmIiLCJzdWIiOiJBdWRTeXMifQ.xNiFKSRciHV7bF7Fi4VdiswLV8INKVi-uc_RgmAeZOCCyQ-AC44Es855t6-qHPL2iQ-c8oevGF9wqVxPjjF8v-QCzE0alTL0blID8HvwMvNp6TBR-cyGgOdeDfFBw3NWKezORMm71qstPxgvHN0KWrLbPFecfRnbn56rsMENq1fa-DPgA8eYupS8jakUEL_9ZHwsTMnlgzCEDLM8mVCmX8RnW1ZmsTzmJ0D5ryXX4EHlbdLNw2er4zbDshbTc7_Vq8Rw8XJLniriTBR5uWMOdmp3WoR-62p182ibS9sNTT0PSjMcIoAb2NJHl6LFkTK098uUPlwUUuqdZH_545sKHg',
            'grpcSignature' => '221c15eb58e250054eacb7d5e87f5d5a',
            'providerCode' => 'SAB',
            'apiUrl' => 'https://p1b3api.bw6688.com',
            'vendorID' => 'oe880dd8en',
            'operatorID' => 'AIXSW',
            'currency' => 82,
            'suffix' => '',
            'currencyConversion' => 1.0
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('BRL');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_SabUSD_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJTQUIiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MjgyODkzMjksImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiOTNiOGQ4MDYyNmZlNWVmNWZjZjYwOWM4MzUxYWRiNmIiLCJzdWIiOiJBdWRTeXMifQ.xNiFKSRciHV7bF7Fi4VdiswLV8INKVi-uc_RgmAeZOCCyQ-AC44Es855t6-qHPL2iQ-c8oevGF9wqVxPjjF8v-QCzE0alTL0blID8HvwMvNp6TBR-cyGgOdeDfFBw3NWKezORMm71qstPxgvHN0KWrLbPFecfRnbn56rsMENq1fa-DPgA8eYupS8jakUEL_9ZHwsTMnlgzCEDLM8mVCmX8RnW1ZmsTzmJ0D5ryXX4EHlbdLNw2er4zbDshbTc7_Vq8Rw8XJLniriTBR5uWMOdmp3WoR-62p182ibS9sNTT0PSjMcIoAb2NJHl6LFkTK098uUPlwUUuqdZH_545sKHg',
            'grpcSignature' => '221c15eb58e250054eacb7d5e87f5d5a',
            'providerCode' => 'SAB',
            'apiUrl' => 'https://p1b3api.bw6688.com',
            'vendorID' => 'oe880dd8en',
            'operatorID' => 'AIXSW',
            'currency' => 3,
            'suffix' => '',
            'currencyConversion' => 1.0
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('USD');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    public static function credentialParameters()
    {
        return [
            ['grpcHost'],
            ['grpcPort'],
            ['grpcToken'],
            ['grpcSignature'],
            ['providerCode'],
            ['apiUrl'],
            ['vendorID'],
            ['operatorID'],
            ['currency'],
            ['suffix'],
            ['currencyConversion']
        ];
    }

    public function getCredentialValue($credentials, $field)
    {
        switch ($field) {
            case 'grpcHost':
                return $credentials->getGrpcHost();
            case 'grpcPort':
                return $credentials->getGrpcPort();
            case 'grpcToken':
                return $credentials->getGrpcToken();
            case 'grpcSignature':
                return $credentials->getGrpcSignature();
            case 'providerCode':
                return $credentials->getProviderCode();
            case 'apiUrl':
                return $credentials->getApiUrl();
            case 'vendorID':
                return $credentials->getVendorID();
            case 'operatorID':
                return $credentials->getOperatorID();
            case 'currency':
                return $credentials->getCurrency();
            case 'suffix':
                return $credentials->getSuffix();
            case 'currencyConversion':
                return $credentials->currencyConversion();
            default:
                return null;
        }
    }
}
