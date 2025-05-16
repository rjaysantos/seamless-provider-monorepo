<?php

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Jdb\JdbApi;
use App\Libraries\LaravelHttpClient;
use Providers\Jdb\JdbEncryption;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Jdb\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class jdbApiTest extends TestCase
{
    private function makeApi(
        $http = null,
        $encryption = null,
    ): JdbApi {
        $http ??= $this->createStub(LaravelHttpClient::class);
        $encryption ??= $this->createStub(JdbEncryption::class);

        return new JdbApi(
            http: $http,
            encryption: $encryption,
        );
    }

    public function test_getGameLaunchUrl_mockEncryption_encrypt()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getParent')->willReturn('testParent');

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        Carbon::setTestNow('2021-01-01 00:00:00');

        $requestData = [
            'action' => 21,
            'ts' => 1609430400000,
            'parent' => 'testParent',
            'uid' => $request->playId,
            'balance' => 1000.00,
            'lang' => $request->language,
            'gType' => '0',
            'mType' => $request->gameId
        ];

        $mockEncryption = $this->createMock(JdbEncryption::class);
        $mockEncryption->expects($this->once())
            ->method('encrypt')
            ->with(
                credentials: $providerCredentials,
                data: $requestData
            );

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'status' => '0000',
                'path' => 'testLaunchUrl.com'
            ]);

        $api = $this->makeApi(
            http: $stubHttp,
            encryption: $mockEncryption
        );
        $api->getGameLaunchUrl(
            credentials: $providerCredentials,
            request: $request,
            balance: 1000.0,
        );

        Carbon::setTestNow();
    }

    public function test_getGameLaunchUrl_mockEncryptionArcade_encrypt()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getParent')->willReturn('testParent');

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '7-8001'
        ]);

        Carbon::setTestNow('2021-01-01 00:00:00');

        $requestData = [
            'action' => 21,
            'ts' => 1609430400000,
            'parent' => 'testParent',
            'uid' => $request->playId,
            'balance' => 1000.00,
            'lang' => $request->language,
            'gType' => '7',
            'mType' => '8001'
        ];

        $mockEncryption = $this->createMock(JdbEncryption::class);
        $mockEncryption->expects($this->once())
            ->method('encrypt')
            ->with(
                credentials: $providerCredentials,
                data: $requestData
            );

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'status' => '0000',
                'path' => 'testLaunchUrl.com'
            ]);

        $api = $this->makeApi(
            http: $stubHttp,
            encryption: $mockEncryption
        );
        $api->getGameLaunchUrl(
            credentials: $providerCredentials,
            request: $request,
            balance: 1000.0
        );

        Carbon::setTestNow();
    }

    public function test_getGameLaunchUrl_mockHttp_post()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getDC')->willReturn('testDC');
        $providerCredentials->method('getApiUrl')->willReturn('testApiUrl.com');

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('encrypt')
            ->willReturn('testEncrypt');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('post')
            ->with(
                url: 'testApiUrl.com/apiRequest.do',
                request: [
                    'dc' => 'testDC',
                    'x' => 'testEncrypt'
                ],
                headers: []
            )
            ->willReturn((object) [
                'status' => '0000',
                'path' => 'testLaunchUrl.com'
            ]);

        $api = $this->makeApi(
            http: $mockHttp,
            encryption: $stubEncryption
        );
        $api->getGameLaunchUrl(
            credentials: $providerCredentials,
            request: $request,
            balance: 1000.0
        );
    }

    #[DataProvider('getGameLaunchUrlResponse')]
    public function test_getGameLaunchUrl_stubHttpMissingResponse_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $apiResponse = [
            'status' => '0000',
            'path' => 'testLaunchUrl.com'
        ];

        unset($apiResponse[$parameter]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLaunchUrl(
            credentials: $providerCredentials,
            request: $request,
            balance: 1000.0
        );
    }

    #[DataProvider('getGameLaunchUrlResponse')]
    public function test_getGameLaunchUrl_stubHttpWrongDataType_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $apiResponse = [
            'status' => '0000',
            'path' => 'testLaunchUrl.com'
        ];

        $apiResponse[$parameter] = 123456;

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLaunchUrl(
            credentials: $providerCredentials,
            request: $request,
            balance: 1000.0
        );
    }

    public static function getGameLaunchUrlResponse()
    {
        return [
            ['status'],
            ['path']
        ];
    }

    public function test_getGameLaunchUrl_stubHttp_expected()
    {
        $expected = 'testLaunchUrl.com';

        $providerCredentials = $this->createMock(ICredentials::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'status' => '0000',
                'path' => 'testLaunchUrl.com'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->getGameLaunchUrl(
            credentials: $providerCredentials,
            request: $request,
            balance: 1000.0
        );

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_queryGameResult_mockEncryption_encrypt()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getParent')->willReturn('testParent');

        Carbon::setTestNow('2021-01-01 00:00:00');

        $requestData = [
            'action' => 54,
            'ts' => 1609430400000,
            'parent' => 'testParent',
            'uid' => 'testPlayID',
            'gType' => 0,
            'historyId' => 'testHistoryID'
        ];

        $mockEncryption = $this->createMock(JdbEncryption::class);
        $mockEncryption->expects($this->once())
            ->method('encrypt')
            ->with(
                credentials: $providerCredentials,
                data: $requestData
            );

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'status' => '0000',
                'data' => [
                    (object) ['path' => 'testVisualUrl.com']
                ]
            ]);

        $api = $this->makeApi(
            http: $stubHttp,
            encryption: $mockEncryption
        );
        $api->queryGameResult(
            credentials: $providerCredentials,
            playID: 'testPlayID',
            historyID: 'testHistoryID',
            gameID: '8001'
        );

        Carbon::setTestNow();
    }

    public function test_queryGameResult_mockEncryptionArcade_encrypt()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getParent')->willReturn('testParent');

        Carbon::setTestNow('2021-01-01 00:00:00');

        $requestData = [
            'action' => 54,
            'ts' => 1609430400000,
            'parent' => 'testParent',
            'uid' => 'testPlayID',
            'gType' => '7',
            'historyId' => 'testHistoryID'
        ];

        $mockEncryption = $this->createMock(JdbEncryption::class);
        $mockEncryption->expects($this->once())
            ->method('encrypt')
            ->with(
                credentials: $providerCredentials,
                data: $requestData
            );

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'status' => '0000',
                'data' => [
                    (object) ['path' => 'testVisualUrl.com']
                ]
            ]);

        $api = $this->makeApi(
            http: $stubHttp,
            encryption: $mockEncryption
        );
        $api->queryGameResult(
            credentials: $providerCredentials,
            playID: 'testPlayID',
            historyID: 'testHistoryID',
            gameID: '7-8001'
        );

        Carbon::setTestNow();
    }

    public function test_queryGameResult_mockHttp_post()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')->willReturn('testApiUrl');
        $providerCredentials->method('getDC')->willReturn('testDC');

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('encrypt')
            ->willReturn('testEncrypt');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->expects($this->once())
            ->method('post')
            ->with(
                url: 'testApiUrl/apiRequest.do',
                request: [
                    'dc' => 'testDC',
                    'x' => 'testEncrypt'
                ],
                headers: []
            )
            ->willReturn((object) [
                'status' => '0000',
                'data' => [
                    (object) ['path' => 'testVisualUrl.com']
                ]
            ]);

        $api = $this->makeApi(
            http: $stubHttp,
            encryption: $stubEncryption
        );
        $api->queryGameResult(
            credentials: $providerCredentials,
            playID: 'testPlayID',
            historyID: 'testHistoryID',
            gameID: 'testGameID'
        );
    }

    #[DataProvider('queryGameResultResponse')]
    public function test_queryGameResult_stubHttpMissingResponse_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $response = [
            'status' => '0000',
            'data' => [
                (object) ['path' => 'testVisualUrl.com']
            ]
        ];

        if (isset($response[$parameter]) === true)
            unset($response[$parameter]);
        else
            unset($response['data'][0]->$parameter);


        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->queryGameResult(
            credentials: $providerCredentials,
            playID: 'testPlayID',
            historyID: 'testHistoryID',
            gameID: 'testGameID'
        );
    }

    #[DataProvider('queryGameResultResponse')]
    public function test_queryGameResult_stubHttpWrongDataType_thirdPartyApiErrorException($parameter, $data)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $response = [
            'status' => '0000',
            'data' => [
                (object) ['path' => 'testVisualUrl.com']
            ]
        ];

        if ($parameter === 'path')
            $response['data'][0]->$parameter = $data;
        else
            $response[$parameter] = $data;

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->queryGameResult(
            credentials: $providerCredentials,
            playID: 'testPlayID',
            historyID: 'testHistoryID',
            gameID: 'testGameID'
        );
    }

    public static function queryGameResultResponse()
    {
        return [
            ['status', 897453453],
            ['data', []],
            ['path', 654123486312]
        ];
    }

    public function test_queryGameResult_stubHttp_expected()
    {
        $expected = 'testVisualUrl.com';

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'status' => '0000',
                'data' => [
                    (object) ['path' => 'testVisualUrl.com']
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->queryGameResult(
            credentials: $providerCredentials,
            playID: 'testPlayID',
            historyID: 'testHistoryID',
            gameID: 'testGameID'
        );

        $this->assertSame(expected: $expected, actual: $response);
    }
}