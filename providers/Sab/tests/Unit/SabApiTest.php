<?php

use Tests\TestCase;
use Providers\Sab\SabApi;
use App\Libraries\LaravelHttpClient;
use Providers\Sab\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class SabApiTest extends TestCase
{
    private function makeApi($http = null): SabApi
    {
        $http ??= $this->createStub(LaravelHttpClient::class);

        return new SabApi(http: $http);
    }

    public function test_createMember_mockHttp_postAsForm()
    {
        $vendorID = 'testVendorID';
        $operatorID = 'testOperatorID';
        $username = 'testUsername';
        $currency = 20;
        $apiUrl = 'http://test-api-url.com';

        $providerCredentials = $this->createMock(ICredentials::class);

        $providerCredentials->method('getVendorID')
            ->willReturn($vendorID);
        $providerCredentials->method('getOperatorID')
            ->willReturn($operatorID);
        $providerCredentials->method('getCurrency')
            ->willReturn($currency);
        $providerCredentials->method('getApiUrl')
            ->willReturn($apiUrl);

        $request = [
            'vendor_id' => $vendorID,
            'operatorId' => $operatorID,
            'vendor_member_id' => $username,
            'username' => $username,
            'currency' => $currency,
            'oddstype' => 3,
        ];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('postAsForm')
            ->with(
                url: $apiUrl . '/api/CreateMember',
                request: $request
            )
            ->willReturn((object) [
                'error_code' => 0
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->createMember(credentials: $providerCredentials, username: $username);
    }

    public function test_createMember_stubHttp_expectedData()
    {
        $expected = 'http://test-url.com';
        $username = 'testUsername';

        $providerCredentials = $this->createMock(ICredentials::class);

        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getOperatorID')
            ->willReturn('testOperatorID');
        $providerCredentials->method('getCurrency')
            ->willReturn(20);
        $providerCredentials->method('getApiUrl')
            ->willReturn($expected);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) ['error_code' => 0]);

        $api = $this->makeApi(http: $stubHttp);
        $api->createMember(credentials: $providerCredentials, username: $username);

        $this->assertSame(expected: $expected, actual: $providerCredentials->getApiUrl());
    }

    public function test_createMember_stubHttpErrorCodeNot0_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $username = 'testUsername';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('test-api-url.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'error_code' => 1
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->createMember(credentials: $providerCredentials, username: $username);
    }

    public function test_createMember_invalidThirdPartyApiResponseParameterType_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $username = 'testUsername';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'error_code' => 'invalid',
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->createMember(credentials: $providerCredentials, username: $username);
    }

    public function test_createMember_missingThirdPartyApiResponseParameter_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $username = 'testUsername';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $apiResponse = [
            'error_code' => 0,
        ];

        unset($apiResponse['error_code']);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object)$apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->createMember(credentials: $providerCredentials, username: $username);
    }

    public function test_getSabaUrl_mockHttp_postAsForm()
    {
        $vendorID = 'testVendorID';
        $apiUrl = 'http://test-api-url.com';
        $username = 'testUsername';
        $device = 1;

        $providerCredentials = $this->createMock(ICredentials::class);

        $providerCredentials->method('getVendorID')
            ->willReturn($vendorID);
        $providerCredentials->method('getApiUrl')
            ->willReturn($apiUrl);

        $request = [
            'vendor_id' => $vendorID,
            'vendor_member_id' => $username,
            'platform' => $device,
        ];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('postAsForm')
            ->with(
                url: $apiUrl . '/api/GetSabaUrl',
                request: $request
            )
            ->willReturn((object) [
                'error_code' => 0,
                'Data' => 'http://test-url.com'
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getSabaUrl(credentials: $providerCredentials, username: $username, device: $device);
    }

    public function test_getSabaUrl_stubHttp_expectedData()
    {
        $expected = 'http://test-url.com';
        $username = 'testUsername';
        $device = 1;

        $providerCredentials = $this->createMock(ICredentials::class);

        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn($expected);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'error_code' => 0,
                'Data' => 'http://test-url.com'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getSabaUrl(credentials: $providerCredentials, username: $username, device: $device);

        $this->assertSame(expected: $expected, actual: $providerCredentials->getApiUrl());
    }

    public function test_getSabaUrl_stubHttpErrorCodeNot0_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $username = 'testUsername';
        $device = 1;

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('test-api-url.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'error_code' => 1,
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getSabaUrl(credentials: $providerCredentials, username: $username, device: $device);
    }

    #[DataProvider('getSabaUrlResponse')]
    public function test_getSabaUrl_invalidThirdPartyApiResponseParameterType_thirdPartyApiErrorException($param)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $username = 'testUsername';
        $device = 1;

        $apiResponse = [
            'error_code' => 0,
            'Data' => 'testUrl.com'
        ];

        if ($param == 'error_code')
            $apiResponse[$param] = 'invalid';
        else
            $apiResponse[$param] = 123;

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getSabaUrl(credentials: $providerCredentials, username: $username, device: $device);
    }

    #[DataProvider('getSabaUrlResponse')]
    public function test_getSabaUrl_missingThirdPartyApiResponseParameter_thirdPartyApiErrorException($param)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $username = 'testUsername';
        $device = 1;

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn('testUrl.com');

        $apiResponse = [
            'error_code' => 0,
            'Data' => 'testUrl.com'
        ];

        unset($apiResponse[$param]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getSabaUrl(credentials: $providerCredentials, username: $username, device: $device);
    }

    public static function getSabaUrlResponse()
    {
        return [
            ['error_code'],
            ['Data'],
        ];
    }

    public function test_getBetDetail_mockHttp_postAsForm()
    {
        $request = [
            'vendor_id' => 'testVendorID',
            'trans_id' => 'testTransactionID'
        ];

        $apiUrl = 'http://test-api-url.com';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn($apiUrl);

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('postAsForm')
            ->with(
                url: $apiUrl . '/api/GetBetDetailByTransID',
                request: $request
            )
            ->willReturn((object) [
                'error_code' => 0,
                'Data' => (object) [
                    'BetDetails' => [
                        (object) [
                            'trans_id' => 'testTransactionID'
                        ]
                    ]
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getBetDetail(credentials: $providerCredentials, transactionID: 'testTransactionID');
    }

    public function test_getBetDetail_stubHttpBetDetails_expectedData()
    {
        $expected = (object) [
            'trans_id' => 'testTransactionID'
        ];

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'error_code' => 0,
                'Data' => (object) [
                    'BetDetails' => [$expected]
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $result = $api->getBetDetail(credentials: $providerCredentials, transactionID: 'testTransactionID');

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBetDetail_stubHttpVirtualSports_expectedData()
    {
        $expected = (object) [
            'trans_id' => 'testTransactionID'
        ];

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'error_code' => 0,
                'Data' => (object) [
                    'BetVirtualSportDetails' => [$expected]
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $result = $api->getBetDetail(credentials: $providerCredentials, transactionID: 'testTransactionID');

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBetDetail_stubHttpNumberGame_expectedData()
    {
        $expected = (object) [
            'trans_id' => 'testTransactionID'
        ];

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'error_code' => 0,
                'Data' => (object) [
                    'BetNumberDetails' => [$expected]
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $result = $api->getBetDetail(credentials: $providerCredentials, transactionID: 'testTransactionID');

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBetDetail_stubHttpErrorCodeNot0_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'error_code' => 1,
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetDetail(credentials: $providerCredentials, transactionID: 'testTransactionID');
    }

    #[DataProvider('getBetDetailParams')]
    public function test_getBetDetail_missingThirdPartyApiResponseParameter_thirdPartyApiErrorException($param)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = [
            'error_code' => 0,
            'Data' => []
        ];

        unset($apiResponse[$param]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetDetail(credentials: $providerCredentials, transactionID: 'testTransactionID');
    }

    public static function getBetDetailParams()
    {
        return [
            ['error_code'],
            ['Data']
        ];
    }

    #[DataProvider('betDetailAllParams')]
    public function test_getBetDetail_invalidThirdPartyApiResponseParameterType_thirdPartyApiErrorException($key, $param)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = [
            'error_code' => 0,
            'Data' => (object) [
                $key => []
            ]
        ];

        if ($param === 'error_code') {
            $apiResponse['error_code'] = 'invalid';
        } elseif ($param === 'Data') {
            $apiResponse['Data'] = 'invalid';
        } elseif ($param === $key) {
            $apiResponse['Data']->$key = 'invalid';
        }

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetDetail(credentials: $providerCredentials, transactionID: 'testTransactionID');
    }

    public static function betDetailAllParams()
    {
        return [
            ['BetDetails', 'error_code'],
            ['BetDetails', 'Data'],
            ['BetDetails', 'BetDetails'],
            ['BetVirtualSportDetails', 'error_code'],
            ['BetVirtualSportDetails', 'Data'],
            ['BetVirtualSportDetails', 'BetVirtualSportDetails'],
            ['BetNumberDetails', 'error_code'],
            ['BetNumberDetails', 'Data'],
            ['BetNumberDetails', 'BetNumberDetails']
        ];
    }
}
