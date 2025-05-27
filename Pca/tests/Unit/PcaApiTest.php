<?php

use Tests\TestCase;
use Providers\Pca\PcaApi;
use Illuminate\Http\Request;
use App\Libraries\LaravelHttpClient;
use Providers\Pca\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class PcaApiTest extends TestCase
{
    private function makeApi($http = null): PcaApi
    {
        $http ??= $this->createStub(LaravelHttpClient::class);

        return new PcaApi(http: $http);
    }

    public function test_getGameLaunchUrl_stubHttpNoResponseCode_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'message' => 200,
                'errors' => (object) [
                    'requestID' => (object) ['The request id field is required.']
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLaunchUrl(credentials: $providerCredentials, request: $request, token: 'testToken');
    }

    #[DataProvider('getGameLaunchUrlParams')]
    public function test_getGameLaunchUrl_stubHttpMissingResponse_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ]);

        $response = [
            'code' => 200,
            'data' => (object) ['url' => 'testUrl.com']
        ];

        if (isset($response[$parameter]) === false)
            unset($response['data']->$parameter);
        else
            unset($response[$parameter]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLaunchUrl(credentials: $providerCredentials, request: $request, token: 'testToken');
    }

    #[DataProvider('getGameLaunchUrlParams')]
    public function test_getGameLaunchUrl_stubHttpInvalidResponseDataType_thirdPartyApiErrorException($parameter, $value)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ]);

        $response = [
            'code' => 200,
            'data' => (object) ['url' => 'testUrl.com']
        ];

        if (isset($response[$parameter]) === false)
            $response['data']->$parameter = $value;
        else
            $response[$parameter] = $value;

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLaunchUrl(credentials: $providerCredentials, request: $request, token: 'testToken');
    }

    public static function getGameLaunchUrlParams()
    {
        return [
            ['code', 'invalid'],
            ['data', 'invalid'],
            ['url', 123]
        ];
    }

    public function test_getGameLaunchUrl_stubHttpCodeNot200_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'code' => 401,
                'data' => (object) ['url' => 'testUrl.com']
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLaunchUrl(credentials: $providerCredentials, request: $request, token: 'testToken');
    }

    public function test_getGameLaunchUrl_stubHttp_expectedData()
    {
        $expected = 'testUrl.com';

        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'code' => 200,
                'data' => (object) ['url' => 'testUrl.com']
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->getGameLaunchUrl(credentials: $providerCredentials, request: $request, token: 'testToken');

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_gameRoundStatus_mockHttp_get()
    {
        $stubCredentials = $this->createMock(ICredentials::class);
        $stubCredentials->method('getAdminKey')->willReturn('testAdminKey');
        $stubCredentials->method('getApiUrl')->willReturn('apiUrl');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('get')
            ->with(
                url: 'apiUrl/reports/gameRoundStatus',
                request: [
                    'game_round' => 'testTransactionID',
                    'timezone' => 'Asia/Kuala_Lumpur'
                ],
                headers: ['x-auth-admin-key' => 'testAdminKey']
            )
            ->willReturn((object) [
                'code' => 200,
                'data' => (object) [
                    'game_history_url' => ['testUrl.com']
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->gameRoundStatus(credentials: $stubCredentials, transactionID: 'testTransactionID');
    }

    #[DataProvider('gameRoundStatusResponse')]
    public function test_gameRoundStatus_stubHttp_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $stubCredentials = $this->createMock(ICredentials::class);

        $response = [
            'code' => 200,
            'data' => (object) [
                'game_history_url' => ['testUrl.com']
            ]
        ];

        if ($parameter == 'game_history_url')
            unset($response['data']->$parameter);
        else if ($parameter == 'url')
            unset($response['data']->game_history_url[0]);
        else
            unset($response[$parameter]);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->gameRoundStatus(credentials: $stubCredentials, transactionID: 'testTransactionID');
    }

    #[DataProvider('gameRoundStatusResponse')]
    public function test_gameRoundStatus_missingApiResponse_ThirdPartyApiErrorException($parameter, $data)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $stubCredentials = $this->createMock(ICredentials::class);

        $response = [
            'code' => 200,
            'data' => (object) [
                'game_history_url' => ['testUrl.com']
            ]
        ];

        if ($parameter == 'game_history_url')
            $response['data']->$parameter = $data;
        else if ($parameter == 'url')
            $response['data']->game_history_url[0] = $data;
        else
            $response[$parameter] = $data;

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->gameRoundStatus(credentials: $stubCredentials, transactionID: 'testTransactionID');
    }

    public static function gameRoundStatusResponse()
    {
        return [
            ['code', 'test'],
            ['data', 123],
            ['game_history_url', 123],
            ['url', 123]
        ];
    }

    public function test_gameRoundStatus_invalidApiResponseType_ThirdPartyApiErrorException()
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
                    'game_history_url' => ['testUrl.com']
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->gameRoundStatus(credentials: $stubCredentials, transactionID: 'testTransactionID');

        $this->assertSame(expected: $expected, actual: $response);
    }
}