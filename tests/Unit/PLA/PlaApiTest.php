<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\GameProviders\V2\PLA\PlaApi;
use App\Libraries\LaravelHttpClient;
use App\GameProviders\V2\PLA\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class PlaApiTest extends TestCase
{
    private function makeApi($http = null): PlaApi
    {
        $http ??= $this->createStub(LaravelHttpClient::class);

        return new PlaApi(http: $http);
    }

    public function test_getGameLaunchUrl_stubHttpNoResponseCode_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $credentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'message' => 200,
                'errors' => (object) [
                    'requestID' => (object) ['The request id field is required.']
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLaunchUrl(credentials: $credentials, request: $request, token: 'testToken');
    }

    public function test_getGameLaunchUrl_stubHttpCodeNot200_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $credentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'code' => 401,
                'data' => (object) [
                    'url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLaunchUrl(credentials: $credentials, request: $request, token: 'testToken');
    }

    public function test_getGameLaunchUrl_stubHttp_expectedData()
    {
        $expected = 'testUrl.com';

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'code' => 200,
                'data' => (object) [
                    'url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->getGameLaunchUrl(credentials: $stubCredentials, request: $request, token: 'testToken');

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_gameRoundStatus_mockCredentials_getAdminKey()
    {
        $mockCredentials = $this->createMock(ICredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getAdminKey')
            ->willReturn('testAdminKey');

        $mockCredentials->method('getApiUrl')
            ->willReturn('apiUrl');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('get')
            ->willReturn((object) [
                'code' => 200,
                'data' => (object) [
                    'game_history_url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->gameRoundStatus(credentials: $mockCredentials, transactionID: 'testTransactionID');
    }

    public function test_gameRoundStatus_mockCredentials_getApiUrl()
    {
        $mockCredentials = $this->createMock(ICredentials::class);
        $mockCredentials->method('getAdminKey')
            ->willReturn('testAdminKey');

        $mockCredentials->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('apiUrl');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('get')
            ->willReturn((object) [
                'code' => 200,
                'data' => (object) [
                    'game_history_url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->gameRoundStatus(credentials: $mockCredentials, transactionID: 'testTransactionID');
    }

    public function test_gameRoundStatus_mockHttp_get()
    {
        $stubCredentials = $this->createMock(ICredentials::class);

        $stubCredentials->method('getAdminKey')
            ->willReturn('testAdminKey');

        $stubCredentials->method('getApiUrl')
            ->willReturn('apiUrl');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('get')
            ->with(
                'apiUrl/reports/gameRoundStatus',
                [
                    'game_round' => 'testTransactionID',
                    'timezone' => 'Asia/Kuala_Lumpur'
                ],
                ['x-auth-admin-key' => 'testAdminKey']
            )
            ->willReturn((object) [
                'code' => 200,
                'data' => (object) [
                    'game_history_url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->gameRoundStatus(credentials: $stubCredentials, transactionID: 'testTransactionID');
    }

    public function test_gameRoundStatus_stubHttp_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $stubCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'code' => 500,
                'data' => null
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->gameRoundStatus(credentials: $stubCredentials, transactionID: 'testTransactionID');
    }

    public function test_gameRoundStatus_stubHttp_expectedData()
    {
        $expected = 'testUrl.com';

        $stubCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'code' => 200,
                'data' => (object) [
                    'game_history_url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->gameRoundStatus(credentials: $stubCredentials, transactionID: 'testTransactionID');

        $this->assertSame(expected: $expected, actual: $response);
    }
}