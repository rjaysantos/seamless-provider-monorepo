<?php

use Tests\TestCase;
use Providers\Ygr\YgrCredentials;
use Providers\Ygr\Contracts\ICredentials;
use Providers\Ygr\Credentials\YgrProduction;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidCurrencyException;

class YgrCredentialsTest extends TestCase
{
    private function makeCredentialSetter(): YgrCredentials
    {
        return new YgrCredentials();
    }

    #[DataProvider('credentialParameters')]
    public function test_getCredentials_production_expectedData($field)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = [
            'grpcHost' => '10.8.134.48',
            'grpcPort' => '3939',
            'grpcToken' => 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJhdWQiOiJZR1IiLCJjYXQiOiJDT01NT04iLCJpYXQiOjE3NDIxNzY3NjQsImlzcyI6IlNlYW1sZXNzV2FsbGV0IiwianRpIjoiMTc4Y2YwMjg0ZDExMmUyOTU1NDdkZGQ1NDY0OWRmNDQiLCJzdWIiOiJBdWRTeXMifQ.bMBSjCyqhpJmlH1WKxGzY3twn_VnqH3td5bDgvO4WsDuh9Pod_mHYnH728BkCXDcbkE96BTnMIYJ25pf7ksMSdnqEr-RbqopfurpJafMiIczHVV5bzFzNaHlLVtcaFvu4c3tg54nm3LqsaHwc7kRL4gbknS5jOnm6xL88nEWUu6x6V50Zjvay1Xi6DXfdrRFJJxSozhvxcgYBudY300Wc1kA8jxniLT0-tpbHbhUhf7UfYiZHMpyXXRLMujNAzqrFVugE-xqBe3no1dswTeSB9aOotoWLf3d0A2ZFANayizE6ql9HXtOuuiTXAmIBrMfCDv-r7tiroaSeT1HJ6tdtg',
            'grpcSignature' => 'cc135a96615e4eee45855a1e614be7e6',
            'providerCode' => 'YGR',
            'apiUrl' => 'https://tyche8w-service.yahutech.com',
            'vendorID' => 'AIX'
        ];

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentials(currency: 'IDR');

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
            ['vendorID']
        ];
    }

    public function getCredentialValue(ICredentials $credentials, string $field): ?string
    {
        return match ($field) {
            'grpcHost' => $credentials->getGrpcHost(),
            'grpcPort' => $credentials->getGrpcPort(),
            'grpcToken' => $credentials->getGrpcToken(),
            'grpcSignature' => $credentials->getGrpcSignature(),
            'providerCode' => $credentials->getProviderCode(),
            'apiUrl' => $credentials->getApiUrl(),
            'vendorID' => $credentials->getVendorID(),
            default => null
        };
    }

    #[DataProvider('validCurrencies')]
    public function test_getCredentials_productionValidCurrencies_expectedData($currency)
    {
        config(['app.env' => 'PRODUCTION']);

        $expectedData = new YgrProduction;

        $credentialSetter = $this->makeCredentialSetter();
        $credentials = $credentialSetter->getCredentials(currency: $currency);

        $this->assertEquals(expected: $expectedData, actual: $credentials);
    }

    public static function validCurrencies()
    {
        return [
            ['IDR'],
            ['PHP'],
            ['THB'],
            ['VND'],
            ['BRL'],
            ['USD']
        ];
    }

    public function test_GetCredentials_productionInvalidCurrency_InvalidCurrencyException()
    {
        $this->expectException(InvalidCurrencyException::class);

        config(['app.env' => 'PRODUCTION']);

        $credentialSetter = $this->makeCredentialSetter();
        $credentialSetter->getCredentials(currency: 'invalidCurrency');
    }
}
