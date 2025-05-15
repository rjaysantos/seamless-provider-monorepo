<?php

use Tests\TestCase;
use Providers\Hg5\Hg5Api;
use App\Libraries\LaravelHttpClient;
use Providers\Hg5\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\ThirdPartyApiErrorException;
use Providers\Hg5\Exceptions\ThirdPartyApiErrorException as ProviderThirdPartyApiErrorException;

class Hg5ApiTest extends TestCase
{
    private function makeApi($http = null): Hg5Api
    {
        $http ??= $this->createStub(LaravelHttpClient::class);

        return new Hg5Api(http: $http);
    }

    public function test_getGameLink_mockHttp_post()
    {
        $apiUrl = 'http://test-api-url.com';
        $account = 'testPlayID';
        $gameCode = 'testGameID';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://test-api-url.com');
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');

        $request = [
            'account' => $account,
            'gamecode' => $gameCode
        ];
        $apiHeader = ['Authorization' => 'validToken'];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('post')
            ->with(
                url: $apiUrl . '/GrandPriest/gamelink',
                request: $request,
                header: $apiHeader
            )
            ->willReturn((object) [
                'data' => (object) [
                    'url' => 'testLaunchUrl',
                    'token' => 'testToken'
                ],
                'status' => (object) [
                    'code' => '0'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getGameLink(credentials: $providerCredentials, playID: $account, gameCode: $gameCode);
    }

    public function test_getGameLink_stubHttp_expectedData()
    {
        $expected = (object) [
            'url' => 'testLaunchUrl',
            'token' => 'testToken'
        ];

        $account = 'testPlayID';
        $gameCode = 'testGameID';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('test-api-url');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'data' => (object) [
                    'url' => 'testLaunchUrl',
                    'token' => 'testToken',
                ],
                'status' => (object) [
                    'code' => '0'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->getGameLink(
            credentials: $providerCredentials,
            playID: $account,
            gameCode: $gameCode
        );

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_getGameLink_stubHttpCodeNot0_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $account = 'testPlayID';
        $gameCode = 'testGameID';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('http://test-api-url.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'status' => (object) [
                    'code' => '1'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLink(credentials: $providerCredentials, playID: $account, gameCode: $gameCode);
    }

    #[DataProvider('getGameLinkResponse')]
    public function test_getGameLink_invalidThirdPartyApiResponseParameterType_thirdPartyApiErrorException($param, $value)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $account = 'testPlayID';
        $gameCode = 'testGameID';

        $apiResponse = [
            'data' => (object) [
                'url' => 'testLaunchUrl',
                'token' => 'testToken',
            ],
            'status' => (object) [
                'code' => '1'
            ]
        ];

        if ($param === 'data' || $param === 'status')
            $apiResponse[$param] = $value;
        elseif ($param === 'url')
            $apiResponse['data']->$param = $value;
        elseif ($param === 'code')
            $apiResponse['status']->$param = $value;

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('http://test-api-url.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLink(credentials: $providerCredentials, playID: $account, gameCode: $gameCode);
    }

    #[DataProvider('getGameLinkResponse')]
    public function test_getGameLink_missingThirdPartyApiResponseParameter_thirdPartyApiErrorException($param)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $account = 'testPlayID';
        $gameCode = 'testGameID';

        $apiResponse = [
            'data' => (object) [
                'url' => 'testLaunchUrl',
                'token' => 'testToken',
            ],
            'status' => (object) [
                'code' => '1'
            ]
        ];

        if ($param === 'data' || $param === 'status')
            unset($apiResponse[$param]);
        elseif ($param === 'url')
            unset($apiResponse['data']->$param);
        elseif ($param === 'code')
            unset($apiResponse['status']->$param);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('http://test-api-url.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLink(credentials: $providerCredentials, playID: $account, gameCode: $gameCode);
    }

    public static function getGameLinkResponse()
    {
        return [
            ['data', 'test'],
            ['url', 123],
            ['token', 123],
            ['status', 'test'],
            ['code', 123]
        ];
    }

    public function test_getOrderDetailLink_mockHttp_get()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://test-api-url.com');
        $providerCredentials->method('getAuthorizationToken')->willReturn('testAuthorizationToken');

        $request = [
            'roundid' => 'testTransactionID',
            'account' => 'testPlayID'
        ];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('get')
            ->with(
                url: 'http://test-api-url.com' . '/GrandPriest/order/detail',
                request: $request,
                header: ['Authorization' => 'testAuthorizationToken']
            )
            ->willReturn((object) [
                'status' => (object) [
                    'code' => '0',
                    'message' => 'testVisualUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getOrderDetailLink(credentials: $providerCredentials, transactionID: 'hg5-testTransactionID', playID: 'testPlayID');
    }

    #[DataProvider('getOrderDetailLinkResponse')]
    public function test_getOrderDetailLink_missingResponse_ThirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $response = [
            'data' => 'testVisualUrl.com',
            'status' => (object) ['code' => '0']
        ];

        if ($parameter == 'code')
            unset($response['status']->code);
        else
            unset($response[$parameter]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->getOrderDetailLink(credentials: $providerCredentials, transactionID: 'hg5-testTransactionID', playID: 'testPlayID');
    }

    #[DataProvider('getOrderDetailLinkResponse')]
    public function test_getOrderDetailLink_invalidResponseType_ThirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $response = [
            'data' => 'testVisualUrl.com',
            'status' => (object) ['code' => '0']
        ];

        if ($parameter === 'code')
            $response['status']->code = 123;
        else
            $response[$parameter] = 123;

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->getOrderDetailLink(credentials: $providerCredentials, transactionID: 'hg5-testTransactionID', playID: 'testPlayID');
    }

    public static function getOrderDetailLinkResponse()
    {
        return [
            ['data'],
            ['status'],
            ['code']
        ];
    }

    public function test_getOrderDetailLink_stubHttpInvalidCode_ThirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('get')
            ->willReturn((object) [
                'data' => 'testVisualUrl.com',
                'status' => (object) ['code' => '453153153351']
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getOrderDetailLink(credentials: $providerCredentials, transactionID: 'hg5-testTransactionID', playID: 'testPlayID');
    }

    public function test_getOrderDetailLink_stubHttp_expectedData()
    {
        $expectedData = 'testVisualUrl.com';

        $providerCredentials = $this->createMock(ICredentials::class);

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('get')
            ->willReturn((object) [
                'status' => (object) [
                    'code' => '0',
                    'message' => 'testVisualUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $response = $api->getOrderDetailLink(credentials: $providerCredentials, transactionID: 'hg5-testTransactionID', playID: 'testPlayID');

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_getOrderQuery_mockHttp_get()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://test-api-url.com');
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');

        $playID = 'testPlayID';
        $startDate = '2024-01-01 00:00:00';
        $endDate = '2024-01-01 00:00:00';

        $apiRequest = [
            'starttime' => $startDate,
            'endtime' => '2024-01-01 00:00:05',
            'page' => 1,
            'account' => $playID
        ];
        $apiHeader = ['Authorization' => 'validToken'];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('get')
            ->with(
                url: 'http://test-api-url.com/GrandPriest/orders',
                request: $apiRequest,
                header: $apiHeader
            )
            ->willReturn((object) [
                'data' => (object) [
                    'list' => [
                        (object) [
                            'gameroundid' => 'testTransactionID',
                            'round' => 'testRoundID',
                            'bet' => 100,
                            'win' => 200
                        ]
                    ]
                ],
                'status' => (object) [
                    'code' => '0'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getOrderQuery(
            credentials: $providerCredentials,
            playID: $playID,
            startDate: $startDate,
            endDate: $endDate
        );
    }

    public function test_getOrderQuery_stubHttp_expectedData()
    {
        $expectedData = collect([
            (object) [
                'gameroundid' => 'testTransactionID',
                'round' => 'testRoundID',
                'bet' => 100,
                'win' => 200
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://test-api-url.com');
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('get')
            ->willReturn((object) [
                'data' => (object) [
                    'list' => [
                        (object) [
                            'gameroundid' => 'testTransactionID',
                            'round' => 'testRoundID',
                            'bet' => 100,
                            'win' => 200
                        ]
                    ]
                ],
                'status' => (object) [
                    'code' => '0'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $response = $api->getOrderQuery(
            credentials: $providerCredentials,
            playID: 'testPlayID',
            startDate: '2024-01-01 00:00:00',
            endDate: '2024-01-01 00:00:00'
        );

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    #[DataProvider('getOrderQueryResponse')]
    public function test_getOrderQuery_missingResponse_expectedData($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $apiResponse = [
            'data' => (object) [
                'list' => [
                    (object) [
                        'gameroundid' => 'testTransactionID',
                        'round' => 'testRoundID',
                        'bet' => 100,
                        'win' => 200
                    ]
                ]
            ],
            'status' => (object) [
                'code' => '0'
            ]
        ];

        if ($parameter == 'data' || $parameter == 'status')
            unset($apiResponse[$parameter]);
        elseif ($parameter == 'code')
            unset($apiResponse['status']->$parameter);
        elseif ($parameter == 'list')
            unset($apiResponse['data']->$parameter);
        else
            unset($apiResponse['data']->list[0]->$parameter);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getOrderQuery(
            credentials: $providerCredentials,
            playID: 'testPlayID',
            startDate: '2024-01-01 00:00:00',
            endDate: '2024-01-01 00:00:00'
        );
    }

    #[DataProvider('getOrderQueryResponse')]
    public function test_getOrderQuery_invalidResponseType_expectedData($parameter, $data)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $apiResponse = [
            'data' => (object) [
                'list' => [
                    (object) [
                        'gameroundid' => 'testTransactionID',
                        'round' => 'testRoundID',
                        'bet' => 100,
                        'win' => 200
                    ]
                ]
            ],
            'status' => (object) [
                'code' => '0'
            ]
        ];

        if ($parameter == 'data' || $parameter == 'status')
            $apiResponse[$parameter] = $data;
        elseif ($parameter == 'code')
            $apiResponse['status']->$parameter = $data;
        elseif ($parameter == 'list')
            $apiResponse['data']->$parameter = $data;
        else
            $apiResponse['data']->list[0]->$parameter = $data;

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getOrderQuery(
            credentials: $providerCredentials,
            playID: 'testPlayID',
            startDate: '2024-01-01 00:00:00',
            endDate: '2024-01-01 00:00:00'
        );
    }

    public static function getOrderQueryResponse()
    {
        return [
            ['data', 123],
            ['list', 123],
            ['gameroundid', 123],
            ['round', 123],
            ['bet', 'test'],
            ['win', 'test'],
            ['status', 123],
            ['code', 123]
        ];
    }

    public function test_getGameList_mockHttp_get()
    {
        $apiUrl = 'http://test-api-url.com';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://test-api-url.com');
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');

        $apiHeader = ['Authorization' => 'validToken'];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('get')
            ->with(
                url: $apiUrl . '/GrandPriest/gameList',
                request: [],
                header: $apiHeader
            )
            ->willReturn((object) [
                'data' => [
                    (object) [
                        'gametype' => 'testLaunchUrl',
                        'gamecode' => 'testToken'
                    ]
                ],
                'status' => (object) [
                    'code' => '0'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getGameList(credentials: $providerCredentials);
    }

    public function test_getGameList_stubHttp_expectedData()
    {
        $expectedData = collect([
            (object) [
                'gametype' => 'testLaunchUrl',
                'gamecode' => 'testToken'
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://test-api-url.com');
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'data' => [
                    (object) [
                        'gametype' => 'testLaunchUrl',
                        'gamecode' => 'testToken'
                    ]
                ],
                'status' => (object) [
                    'code' => '0'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->getGameList(credentials: $providerCredentials);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_getGameList_stubHttpStatusNot0_ProviderThirdPartyException()
    {
        $this->expectException(ProviderThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://test-api-url.com');
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'data' => null,
                'status' => (object) [
                    'code' => '5135435315'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameList(credentials: $providerCredentials);
    }

    #[DataProvider('getGameListResponse')]
    public function test_getGameList_missingResponse_ProviderThirdPartyException($parameter)
    {
        $this->expectException(ProviderThirdPartyApiErrorException::class);

        $apiResponse = [
            'data' => [
                (object) [
                    'gametype' => 'testLaunchUrl',
                    'gamecode' => 'testToken'
                ]
            ],
            'status' => (object) [
                'code' => '0'
            ]
        ];

        if ($parameter == 'data' || $parameter == 'status')
            unset($apiResponse[$parameter]);
        else if ($parameter == 'code')
            unset($apiResponse['status']->code);
        else
            unset($apiResponse['data'][0]->$parameter);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://test-api-url.com');
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameList(credentials: $providerCredentials);
    }

    #[DataProvider('getGameListResponse')]
    public function test_getGameList_invalidResponseType_ProviderThirdPartyException($parameter)
    {
        $this->expectException(ProviderThirdPartyApiErrorException::class);

        $apiResponse = [
            'data' => [
                (object) [
                    'gametype' => 'testLaunchUrl',
                    'gamecode' => 'testToken'
                ]
            ],
            'status' => (object) [
                'code' => '0'
            ]
        ];

        if ($parameter == 'data' || $parameter == 'status')
            $apiResponse[$parameter] = 123;
        else if ($parameter == 'code')
            $apiResponse['status']->code = 123;
        else
            $apiResponse['data'][0]->$parameter = 123;

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://test-api-url.com');
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameList(credentials: $providerCredentials);
    }

    public static function getGameListResponse(): array
    {
        return [
            ['data'],
            ['gametype'],
            ['gamecode'],
            ['status'],
            ['code'],
        ];
    }
}
