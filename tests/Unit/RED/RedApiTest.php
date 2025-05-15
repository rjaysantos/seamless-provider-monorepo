<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\GameProviders\V2\Red\RedApi;
use App\Libraries\LaravelHttpClient;
use PHPUnit\Framework\Attributes\DataProvider;
use App\GameProviders\V2\Red\Contracts\ICredentials;
use App\GameProviders\V2\Red\Credentials\RedStaging;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class RedApiTest extends TestCase
{
    private function makeApi($http = null): RedApi
    {
        $http ??= $this->createStub(LaravelHttpClient::class);

        return new RedApi(http: $http);
    }

    public function test_authenticate_mockHttp_post()
    {
        $credentials = $this->createMock(RedStaging::class);
        $credentials->method('getPrdID')
            ->willReturn(111);
        $credentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');
        $credentials->method('getCode')
            ->willReturn('testCode');
        $credentials->method('getToken')
            ->willReturn('testToken');

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);
        $balance = 100.00;

        $apiRequest = [
            'user' => [
                'id' => $request->memberId,
                'name' => $request->playId,
                'balance' => $balance,
                'language' => 'en',
                'domain_url' => $request->host,
                'currency' => $request->currency
            ],
            'prd' => [
                'id' => 111,
                'type' => $request->gameId,
                'is_mobile' => $request->device == 0 ? true : false
            ]
        ];

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->expects($this->once())
            ->method('post')
            ->with(
                url: 'testApiUrl.com/auth',
                request: $apiRequest,
                headers: [
                    'ag-code' => 'testCode',
                    'ag-token' => 'testToken',
                    'content-type' => 'application/json'
                ]
            )
            ->willReturn((object) [
                'launch_url' => 'testUrl.com',
                'user_id' => 123,
                'status' => 1
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->authenticate(
            credentials: $credentials,
            request: $request,
            balance: $balance
        );
    }

    public function test_authenticate_stubHttp_expectedData()
    {
        $expected = (object) [
            'userID' => 123,
            'launchUrl' => 'testUrl.com'
        ];

        $credentials = $this->createMock(RedStaging::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'launch_url' => 'testUrl.com',
                'user_id' => 123,
                'status' => 1
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->authenticate(
            credentials: $credentials,
            request: $request,
            balance: 100.00
        );

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_authenticate_stubHttpStatusNot1_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $credentials = $this->createMock(RedStaging::class);
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'launch_url' => 'testUrl.com',
                'user_id' => 123,
                'status' => 321
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->authenticate(
            credentials: $credentials,
            request: $request,
            balance: 100.00
        );
    }

    #[DataProvider('authenticateResponse')]
    public function test_authenticate_stubHttpMissingResponse_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $credentials = $this->createMock(RedStaging::class);
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $apiResponse = [
            'launch_url' => 'testUrl.com',
            'user_id' => 123,
            'status' => 1
        ];

        unset($apiResponse[$parameter]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->authenticate(
            credentials: $credentials,
            request: $request,
            balance: 100.00
        );
    }

    #[DataProvider('authenticateResponse')]
    public function test_authenticate_stubHttpWrongDataType_thirdPartyApiErrorException($parameter, $data)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $credentials = $this->createMock(RedStaging::class);
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $apiResponse = [
            'launch_url' => 'testUrl.com',
            'user_id' => 123,
            'status' => 1
        ];

        $apiResponse[$parameter] = $data;

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->authenticate(
            credentials: $credentials,
            request: $request,
            balance: 100.00
        );
    }

    public static function authenticateResponse()
    {
        return [
            ['launch_url', 123],
            ['user_id', 'testUserID'],
            ['status', 'testStatus']
        ];
    }

    public function test_getBetDetailUrl_mockHttp_post()
    {
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');
        $credentials->method('getPrdID')
            ->willReturn(213);
        $credentials->method('getCode')
            ->willReturn('testCode');
        $credentials->method('getToken')
            ->willReturn('testToken');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('post')
            ->with(
                url: 'testApiUrl.com/bet/results',
                request: [
                    'prd_id' => 213,
                    'txn_id' => 'testTransactionID',
                    'lang' => 'en'
                ],
                headers: [
                    'ag-code' => 'testCode',
                    'ag-token' => 'testToken',
                    'content-type' => 'application/json'
                ]
            )
            ->willReturn((object) [
                'status' => 1,
                'url' => 'testVisualUrl.com'
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getBetResult(
            credentials: $credentials,
            transactionID: 'testTransactionID'
        );
    }

    public function test_getBetDetailUrl_stubHttp_expectedData()
    {
        $expected = 'testVisualUrl.com';

        $credentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'status' => 1,
                'url' => 'testVisualUrl.com'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->getBetResult(
            credentials: $credentials,
            transactionID: 'testTransactionID'
        );

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_getBetDetailUrl_stubHttpStatusNot1_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $credentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'status' => 68645315,
                'url' => 'testVisualUrl.com'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetResult(
            credentials: $credentials,
            transactionID: 'testTransactionID'
        );
    }

    #[DataProvider('betResultParams')]
    public function test_getBetDetailUrl_stubHttpMissingResponse_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $credentials = $this->createMock(ICredentials::class);

        $apiResponse = [
            'status' => 1,
            'url' => 'testVisualUrl.com'
        ];

        unset($apiResponse[$parameter]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetResult(
            credentials: $credentials,
            transactionID: 'testTransactionID'
        );
    }

    #[DataProvider('betResultParams')]
    public function test_getBetDetailUrl_stubHttpWrongDataType_thirdPartyApiErrorException($parameter, $data)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $credentials = $this->createMock(ICredentials::class);

        $apiResponse = [
            'status' => 1,
            'url' => 'testVisualUrl.com'
        ];

        $apiResponse[$parameter] = $data;

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBetResult(
            credentials: $credentials,
            transactionID: 'testTransactionID'
        );
    }

    public static function betResultParams()
    {
        return [
            ['status', '123'],
            ['url', 123]
        ];
    }
}