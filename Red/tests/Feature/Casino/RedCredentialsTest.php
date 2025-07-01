<?php

use Tests\TestCase;
use Providers\Red\RedCredentials;
use Providers\Red\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidCurrencyException;

class RedCredentialsTest extends TestCase
{
    private function makeCredentialSetter(): RedCredentials
    {
        return new RedCredentials();
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_RedIDR_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJSRUQiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTUxNjM2NDAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZWY3OTBhZTkwY2JlNzJmMzI1MjVmM2IxMzQ5YzE1YjAiLCJzdWIiOiJBdWRTeXMifQ.hA_sxFutlRj8Y4PG_6K3-lC3ObOUaI_7x_H7NRj8Diw8kQjL3k0lboU4e074oiT_uHiYP1i_S54XwFm-eGr8HZNtlZX1Z7e3gsgRYxe0EsHR60OPZOsdY3kn3UWw452--4miBJJVc0q5hMckJ5REjT2-IrJgNAunODFRTYYpsoxS5Q_KiE8Lg8HinDiq8rrsKAVoPXs_1fCrt6VyIP8HLiZs-bPtJRPIcXQoAF6VJTRLyDBDwtlp3wAucJ1XPnGYYUsIrwMSFE7u1N-FBJXyz-vAEdMwSkFXC0AAnDxe-SYylTLRNatc7K3TdiCJ29-rD4xB5FUZZ0H9vkJFwe39aA',
            'grpcSignature' => 'fc681d6722a30e2533226eeb580047a2',
            'providerCode' => 'RED',
            'apiUrl' => 'https://ps9games.com',
            'code' => 'EQX0131',
            'token' => 'T3Z0Jehp9opibWc7fu7bgEzszHuCemQP',
            'prdID' => 290,
            'secretKey' => '0Xiq3O0z7wtfCOFGKQ6tfn5SOqBgR2HT'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('IDR');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_RedBRL_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJSRUQiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTUxNjM2NDAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZWY3OTBhZTkwY2JlNzJmMzI1MjVmM2IxMzQ5YzE1YjAiLCJzdWIiOiJBdWRTeXMifQ.hA_sxFutlRj8Y4PG_6K3-lC3ObOUaI_7x_H7NRj8Diw8kQjL3k0lboU4e074oiT_uHiYP1i_S54XwFm-eGr8HZNtlZX1Z7e3gsgRYxe0EsHR60OPZOsdY3kn3UWw452--4miBJJVc0q5hMckJ5REjT2-IrJgNAunODFRTYYpsoxS5Q_KiE8Lg8HinDiq8rrsKAVoPXs_1fCrt6VyIP8HLiZs-bPtJRPIcXQoAF6VJTRLyDBDwtlp3wAucJ1XPnGYYUsIrwMSFE7u1N-FBJXyz-vAEdMwSkFXC0AAnDxe-SYylTLRNatc7K3TdiCJ29-rD4xB5FUZZ0H9vkJFwe39aA',
            'grpcSignature' => 'fc681d6722a30e2533226eeb580047a2',
            'providerCode' => 'RED',
            'apiUrl' => 'https://ps9games.com',
            'code' => 'EQX0135',
            'token' => 'CUPeILmzwoSpsdem0EUDzASxhMV81byN',
            'prdID' => 259,
            'secretKey' => 'qb82MAdwnQTPvcQRpmUg6CUriM3fEjnn'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('BRL');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_RedPHP_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJSRUQiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTUxNjM2NDAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZWY3OTBhZTkwY2JlNzJmMzI1MjVmM2IxMzQ5YzE1YjAiLCJzdWIiOiJBdWRTeXMifQ.hA_sxFutlRj8Y4PG_6K3-lC3ObOUaI_7x_H7NRj8Diw8kQjL3k0lboU4e074oiT_uHiYP1i_S54XwFm-eGr8HZNtlZX1Z7e3gsgRYxe0EsHR60OPZOsdY3kn3UWw452--4miBJJVc0q5hMckJ5REjT2-IrJgNAunODFRTYYpsoxS5Q_KiE8Lg8HinDiq8rrsKAVoPXs_1fCrt6VyIP8HLiZs-bPtJRPIcXQoAF6VJTRLyDBDwtlp3wAucJ1XPnGYYUsIrwMSFE7u1N-FBJXyz-vAEdMwSkFXC0AAnDxe-SYylTLRNatc7K3TdiCJ29-rD4xB5FUZZ0H9vkJFwe39aA',
            'grpcSignature' => 'fc681d6722a30e2533226eeb580047a2',
            'providerCode' => 'RED',
            'apiUrl' => 'https://ps9games.com',
            'code' => 'EQX0132',
            'token' => 'pmdDsbAavXbnlEXBEU6JHBi0Zk9YEhKJ',
            'prdID' => 213,
            'secretKey' => 'f5jdvTNoX67AJjgbHny97ndYfp7wPdhW'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('PHP');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_RedTHB_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJSRUQiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTUxNjM2NDAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZWY3OTBhZTkwY2JlNzJmMzI1MjVmM2IxMzQ5YzE1YjAiLCJzdWIiOiJBdWRTeXMifQ.hA_sxFutlRj8Y4PG_6K3-lC3ObOUaI_7x_H7NRj8Diw8kQjL3k0lboU4e074oiT_uHiYP1i_S54XwFm-eGr8HZNtlZX1Z7e3gsgRYxe0EsHR60OPZOsdY3kn3UWw452--4miBJJVc0q5hMckJ5REjT2-IrJgNAunODFRTYYpsoxS5Q_KiE8Lg8HinDiq8rrsKAVoPXs_1fCrt6VyIP8HLiZs-bPtJRPIcXQoAF6VJTRLyDBDwtlp3wAucJ1XPnGYYUsIrwMSFE7u1N-FBJXyz-vAEdMwSkFXC0AAnDxe-SYylTLRNatc7K3TdiCJ29-rD4xB5FUZZ0H9vkJFwe39aA',
            'grpcSignature' => 'fc681d6722a30e2533226eeb580047a2',
            'providerCode' => 'RED',
            'apiUrl' => 'https://ps9games.com',
            'code' => 'EQX0133',
            'token' => 'GF7VB2ZI8mp7tiJzIw6pssrXau7hfBDQ',
            'prdID' => 213,
            'secretKey' => '6Vn34hnjcj7rT100Q0qGrbdJwesvqooS'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('THB');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_RedUSD_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJSRUQiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTUxNjM2NDAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZWY3OTBhZTkwY2JlNzJmMzI1MjVmM2IxMzQ5YzE1YjAiLCJzdWIiOiJBdWRTeXMifQ.hA_sxFutlRj8Y4PG_6K3-lC3ObOUaI_7x_H7NRj8Diw8kQjL3k0lboU4e074oiT_uHiYP1i_S54XwFm-eGr8HZNtlZX1Z7e3gsgRYxe0EsHR60OPZOsdY3kn3UWw452--4miBJJVc0q5hMckJ5REjT2-IrJgNAunODFRTYYpsoxS5Q_KiE8Lg8HinDiq8rrsKAVoPXs_1fCrt6VyIP8HLiZs-bPtJRPIcXQoAF6VJTRLyDBDwtlp3wAucJ1XPnGYYUsIrwMSFE7u1N-FBJXyz-vAEdMwSkFXC0AAnDxe-SYylTLRNatc7K3TdiCJ29-rD4xB5FUZZ0H9vkJFwe39aA',
            'grpcSignature' => 'fc681d6722a30e2533226eeb580047a2',
            'providerCode' => 'RED',
            'apiUrl' => 'https://ps9games.com',
            'code' => 'EQX0136',
            'token' => 'WR9ZYDDJjOY1vEImqCmT2W7y4HDNBfiH',
            'prdID' => 213,
            'secretKey' => 'hnW7Yti2NGEo7cawCyWWF7ilzcLZjmyk'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('USD');

        $this->assertSame(
            expected: $expectedData[$field],
            actual: $this->getCredentialValue(credentials: $credentials, field: $field)
        );
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentialsByCurrency_RedVND_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJSRUQiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3MTUxNjM2NDAsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiZWY3OTBhZTkwY2JlNzJmMzI1MjVmM2IxMzQ5YzE1YjAiLCJzdWIiOiJBdWRTeXMifQ.hA_sxFutlRj8Y4PG_6K3-lC3ObOUaI_7x_H7NRj8Diw8kQjL3k0lboU4e074oiT_uHiYP1i_S54XwFm-eGr8HZNtlZX1Z7e3gsgRYxe0EsHR60OPZOsdY3kn3UWw452--4miBJJVc0q5hMckJ5REjT2-IrJgNAunODFRTYYpsoxS5Q_KiE8Lg8HinDiq8rrsKAVoPXs_1fCrt6VyIP8HLiZs-bPtJRPIcXQoAF6VJTRLyDBDwtlp3wAucJ1XPnGYYUsIrwMSFE7u1N-FBJXyz-vAEdMwSkFXC0AAnDxe-SYylTLRNatc7K3TdiCJ29-rD4xB5FUZZ0H9vkJFwe39aA',
            'grpcSignature' => 'fc681d6722a30e2533226eeb580047a2',
            'providerCode' => 'RED',
            'apiUrl' => 'https://ps9games.com',
            'code' => 'EQX0134',
            'token' => 'c0LnVGlqVMvtNU8vYPEuFcBj6gPTsIHx',
            'prdID' => 213,
            'secretKey' => '1HSqJfuzrqnVHdZmnw4bdhyD8hDQmqA8'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentialsByCurrency('VND');

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
            ['code'],
            ['token'],
            ['prdID'],
            ['secretKey']
        ];
    }

    public function getCredentialValue(ICredentials $credentials, string $field)
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
            case 'code':
                return $credentials->getCode();
            case 'token':
                return $credentials->getToken();
            case 'prdID':
                return $credentials->getPrdID();
            case 'secretKey':
                return $credentials->getSecretKey();
            default:
                return null;
        }
    }

    public function test_getCredentialsByCurrency_productionInvalidCurrency_InvalidCurrencyException()
    {
        $this->expectException(InvalidCurrencyException::class);

        config(['app.env' => 'PRODUCTION']);

        $credentialSetter = $this->makeCredentialSetter();
        $credentialSetter->getCredentialsByCurrency(currency: 'invalidCurrency');
    }
}
