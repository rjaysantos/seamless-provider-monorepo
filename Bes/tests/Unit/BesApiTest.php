<?php

use Tests\TestCase;
use Providers\Bes\BesApi;
use App\Libraries\LaravelHttpClient;
use Providers\Bes\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class BesApiTest extends TestCase
{
    private function makeApi($http = null): BesApi
    {
        $http ??= $this->createStub(LaravelHttpClient::class);

        return new BesApi(http: $http);
    }

    public function test_getDetailsUrl_mockHttp_postAsForm()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCert')->willReturn('testCert');
        $providerCredentials->method('getAgentID')->willReturn('testAgentID');

        $request = [
            'cert' => 'testCert',
            'extension1' => 'testAgentID',
            'transId' => 'testTransID',
            'lang' => 'en'
        ];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('postAsForm')
            ->with(
                url: 'http://testApiUrl.com/api/game/getdetailsurl',
                request: $request
            )
            ->willReturn((object) [
                'status' => 1,
                'logurl' => 'testVisualUrl.com'
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getDetailsUrl($providerCredentials,  'testTransID');
    }

    public function test_getDetailsUrl_stubHttp_expectedData()
    {
        $expected = 'testVisualUrl.com';

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCert')->willReturn('testCert');
        $providerCredentials->method('getAgentID')->willReturn('testAgentID');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'status' => 1,
                'logurl' => 'testVisualUrl.com'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $result = $api->getDetailsUrl($providerCredentials,  'testTransID');

        $this->assertSame(expected: $expected, actual: $result);
    }

    public function test_getDetailsUrl_stubHttpStatusNot1_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCert')->willReturn('testCert');
        $providerCredentials->method('getAgentID')->willReturn('testAgentID');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) ['status' => 0]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getDetailsUrl($providerCredentials,  'testTransID');
    }

    #[DataProvider('getDetailUrlResponse')]
    public function test_getDetailsUrl_invalidThirdPartyApiResponseParameter_thirdPartyApiErrorException($param, $value)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = [
            'status' => 1,
            'logurl' => 'testVisualUrl.com'
        ];

        $apiResponse[$param] = $value;

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCert')->willReturn('testCert');
        $providerCredentials->method('getAgentID')->willReturn('testAgentID');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getDetailsUrl($providerCredentials,  'testTransID');
    }

    #[DataProvider('getDetailUrlResponse')]
    public function test_getDetailsUrl_missingThirdPartyApiResponseParameterType_thirdPartyApiErrorException($param)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = [
            'status' => 1,
            'logurl' => 'testVisualUrl.com'
        ];

        unset($apiResponse[$param]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCert')->willReturn('testCert');
        $providerCredentials->method('getAgentID')->willReturn('testAgentID');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getDetailsUrl($providerCredentials,  'testTransID');
    }

    public static function getDetailUrlResponse()
    {
        return [
            ['status', ''],
            ['logurl', 123]
        ];
    }

    public function test_getKey_mockHttp_postAsForm()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getCert')->willReturn('testCert');
        $providerCredentials->method('getAgentID')->willReturn('testAgentID');

        $request = [
            'cert' => 'testCert',
            'user' => 'testPlayID',
            'extension1' => 'testAgentID'
        ];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('postAsForm')
            ->with(
                url: 'http://testApiUrl.com/api/game/getKey',
                request: $request
            )
            ->willReturn((object) [
                'status' => 1,
                'returnurl' => 'testLaunchUrl.com'
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getKey($providerCredentials,  'testPlayID');
    }

    public function test_getKey_stubHttp_expectedData()
    {
        $expected = 'testLaunchUrl.com';

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) [
                'status' => 1,
                'returnurl' => 'testLaunchUrl.com'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $result = $api->getKey($providerCredentials,  'testPlayID');

        $this->assertSame(expected: $expected, actual: $result);
    }

    public function test_getKey_stubHttpStatusNot1_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) ['status' => 0]);

        $api = $this->makeApi($stubHttp);
        $api->getKey($providerCredentials,  'testPlayID');
    }

    #[DataProvider('getKeyResponse')]
    public function test_getKey_invalidThirdPartyApiResponseParameterType_thirdPartyApiErrorException($param, $value)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = [
            'status' => 1,
            'returnurl' => 'testLaunchUrl.com',
        ];

        $apiResponse[$param] = $value;

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getKey($providerCredentials,  'testPlayID');
    }

    #[DataProvider('getKeyResponse')]
    public function test_getKey_missingThirdPartyApiResponseParameterType_thirdPartyApiErrorException($param)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = [
            'status' => 1,
            'returnurl' => 'testLaunchUrl.com',
        ];

        unset($apiResponse[$param]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getKey($providerCredentials,  'testPlayID');
    }

    public static function getKeyResponse()
    {
        return [
            ['status', ''],
            ['returnurl', 123]
        ];
    }

    public function test_getGameList_mockHttp_postAsForm()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('http://testApiUrl.com');
        $providerCredentials->method('getAgentID')->willReturn('testAgentID');

        $request = [
            'extension1' => 'testAgentID'
        ];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('postAsForm')
            ->with(
                url: 'http://testApiUrl.com/api/game/subgamelist',
                request: $request
            )
            ->willReturn((object) [
                'gamelist' => [
                    (object)[
                        'gid' => 'test1',
                        'SortID' => 3
                    ],
                    (object)[
                        'gid' => 'test2',
                        'SortID' => 2
                    ],
                    (object)[
                        'gid' => 'test3',
                        'SortID' => 1
                    ],
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getGameList(credentials: $providerCredentials);
    }

    public function test_getGameList_stubHttp_expectedData()
    {
        $expected = [
            (object)[
                'gid' => 'test1',
                'SortID' => 3
            ],
            (object)[
                'gid' => 'test2',
                'SortID' => 2
            ],
            (object)[
                'gid' => 'test3',
                'SortID' => 1
            ],
        ];

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object)['gamelist' => $expected]);

        $api = $this->makeApi(http: $stubHttp);
        $result = $api->getGameList(credentials: $providerCredentials);

        $this->assertSame(expected: $expected, actual: $result);
    }

    #[DataProvider('gameListResponseParams')]
    public function test_getGameList_invalidThirdPartyResponseParameterType_thirdPartyErrorException($param, $value)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = (object) [
            'gamelist' => [
                (object)[
                    'gid' => 'test1',
                    'SortID' => 3
                ],
                (object)[
                    'gid' => 'test2',
                    'SortID' => 2
                ],
                (object)[
                    'gid' => 'test3',
                    'SortID' => 1
                ],
            ]
        ];

        if ($param === 'gamelist')
            $apiResponse->gamelist = $value;
        else
            $apiResponse->gamelist[0]->$param = $value;

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn($apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameList(credentials: $providerCredentials);
    }

    #[DataProvider('gameListResponseParams')]
    public function test_getGameList_missingThirdPartyResponseParameterType_thirdPartyErrorException($param)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $apiResponse = (object) [
            'gamelist' => [
                (object)[
                    'gid' => 'test1',
                    'SortID' => 3
                ],
                (object)[
                    'gid' => 'test2',
                    'SortID' => 2
                ],
                (object)[
                    'gid' => 'test3',
                    'SortID' => 1
                ],
            ]
        ];

        if ($param === 'gamelist')
            unset($apiResponse->$param);
        else
            unset($apiResponse->gamelist[0]->$param);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn($apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameList(credentials: $providerCredentials);
    }

    public static function gameListResponseParams()
    {
        return [
            ['gamelist', ''],
            ['gid', 123],
            ['SortID', '']
        ];
    }

    public function test_updateGamePosition_mockHttp_postAsForm()
    {
        $gameCodes = ['test3', 'test2', 'test1'];

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getNavigationApiBearerToken')->willReturn('testNavApiToken');
        $providerCredentials->method('getNavigationApiUrl')->willReturn('http://testNavApiUrl.com');

        $request = [
            'providerCode' => 'BES',
            'gameCode' => $gameCodes,
        ];

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('postAsForm')
            ->with(
                url: 'http://testNavApiUrl.com/api/games/update-game-position',
                request: $request,
                header: ['Authorization' => 'Bearer testNavApiToken']
            )
            ->willReturn((object) ['code' => 9401]);

        $api = $this->makeApi(http: $mockHttp);
        $api->updateGamePosition(credentials: $providerCredentials, gameCodes: $gameCodes);
    }

    public function test_updateGamePosition_stubHttp_expectedData()
    {
        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object)['code' => 9401]);

        $api = $this->makeApi(http: $stubHttp);
        $result = $api->updateGamePosition(credentials: $providerCredentials, gameCodes: ['test3', 'test2', 'test1']);

        $this->assertNull(actual: $result);
    }

    #[DataProvider('NavApiResponseParams')]
    public function test_updateGamePosition_stubHttpInvalidThirdPartyResponse_thirdPartyApiErrorException($value)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getNavigationApiBearerToken')->willReturn('testNavApiToken');
        $providerCredentials->method('getNavigationApiUrl')->willReturn('http://testNavApiUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) ['code' => $value]);

        $api = $this->makeApi(http: $stubHttp);
        $api->updateGamePosition(credentials: $providerCredentials, gameCodes: ['test3', 'test2', 'test1']);
    }

    public static function NavApiResponseParams()
    {
        return [
            [9402],
            ['invalid']
        ];
    }

    public function test_updateGamePosition_stubHttpMissingThirdPartyResponse_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getNavigationApiBearerToken')->willReturn('testNavApiToken');
        $providerCredentials->method('getNavigationApiUrl')->willReturn('http://testNavApiUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('postAsForm')
            ->willReturn((object) []);

        $api = $this->makeApi(http: $stubHttp);
        $api->updateGamePosition(credentials: $providerCredentials, gameCodes: ['test3', 'test2', 'test1']);
    }
}
