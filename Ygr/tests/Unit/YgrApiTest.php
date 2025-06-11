<?php

use Tests\TestCase;
use Providers\Ygr\YgrApi;
use App\Libraries\LaravelHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Ygr\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class YgrApiTest extends TestCase
{
    private function makeApi($http = null): YgrApi
    {
        $http ??= $this->createStub(LaravelHttpClient::class);

        return new YgrApi(http: $http);
    }

    #[DataProvider('providerLanguages')]
    public function test_launch_mockHttp_get($language, $providerLanguage)
    {
        $token = 'testToken';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');
        $providerCredentials->method('getVendorID')
            ->willReturn('testSupplier');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('get')
            ->with(
                url: $providerCredentials->getApiUrl() . '/launch',
                request: [
                    'token' => $token,
                    'language' => $providerLanguage
                ],
                headers: ['Supplier' => 'testSupplier']
            )
            ->willReturn((object) [
                'ErrorCode' => 0,
                'Data' => (object) [
                    'Url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->launch(credentials: $providerCredentials, token: $token, language: $language);
    }

    public static function providerLanguages()
    {
        return [
            ['id', 'id-ID'],
            ['ph', 'en-US'],
            ['th', 'th-TH'],
            ['vn', 'vi-VN'],
            ['br', 'pt-BR'],
            ['en', 'en-US'],
            ['ms', 'en-US']
        ];

        // IDR PHP THB VND BRL USD MYR
    }
    public function test_launch_stubHttp_expectedData()
    {
        $expected = 'testUrl.com';

        $token = 'testToken';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'ErrorCode' => 0,
                'Data' => (object) [
                    'Url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->launch(credentials: $providerCredentials, token: $token, language: 'id');

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_launch_stubHttpErrorCodeNot200_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);
        $token = 'testToken';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'ErrorCode' => 100
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->launch(credentials: $providerCredentials, token: $token, language: 'id');
    }

    #[DataProvider('apiResponse')]
    public function test_launch_stubHttpMissingResponse_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);
        $token = 'testToken';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $apiResponse = [
            'ErrorCode' => 0,
            'Data' => [
                'Url' => 'testUrl.com'
            ]
        ];

        if (isset($apiResponse[$parameter]) === true)
            unset($apiResponse[$parameter]);
        else
            unset($apiResponse['Data'][$parameter]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->launch(credentials: $providerCredentials, token: $token, language: 'id');
    }

    #[DataProvider('visualLanguages')]
    public function test_getBetDetailUrl_mockHttpMultipleLanguage_post($currency, $language)
    {
        $transactionID = 'testTransactionID';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('post')
            ->with(
                url: $providerCredentials->getApiUrl() . '/GetGameDetailUrl',
                request: [
                    'WagersId' => $transactionID,
                    'Lang' => $language
                ]
            )
            ->willReturn((object) [
                'ErrorCode' => 0,
                'Data' => (object) [
                    'Url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getBetDetailUrl(credentials: $providerCredentials, transactionID: $transactionID, currency: $currency);
    }

    public static function visualLanguages()
    {
        return [
            ['IDR', 'id-ID'],
            ['PHP', 'en-US'],
            ['THB', 'th-TH'],
            ['VND', 'vi-VN'],
            ['BRL', 'pt-BR'],
            ['USD', 'en-US'],
            ['MYR', 'en-US']
        ];
    }

    public function test_getBetDetailUrl_stubHttp_expectedData()
    {
        $expected = 'testUrl.com';

        $transactionID = 'testTransactionID';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'ErrorCode' => 0,
                'Data' => (object) [
                    'Url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->getBetDetailUrl(credentials: $providerCredentials, transactionID: $transactionID, currency: 'IDR');

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_getBetDetailUrl_stubHttp_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $transactionID = 'testTransactionID';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'ErrorCode' => 989798,
                'Data' => (object) []
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetDetailUrl(credentials: $providerCredentials, transactionID: $transactionID, currency: 'IDR');
    }

    #[DataProvider('apiResponse')]
    public function test_getBetDetailUrl_stubHttpMissingResponse_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $transactionID = 'testTransactionID';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $apiResponse = [
            'ErrorCode' => 0,
            'Data' => [
                'Url' => 'testUrl.com'
            ]
        ];

        if (isset($apiResponse[$parameter]) === true)
            unset($apiResponse[$parameter]);
        else
            unset($apiResponse['Data'][$parameter]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'ErrorCode' => 989798,
                'Data' => (object) []
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetDetailUrl(credentials: $providerCredentials, transactionID: $transactionID, currency: 'IDR');
    }

    public static function apiResponse()
    {
        return [
            ['ErrorCode'],
            ['Data'],
            ['Url']
        ];
    }
}
