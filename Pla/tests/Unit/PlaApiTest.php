<?php

use Tests\TestCase;
use Providers\Pla\PlaApi;
use Illuminate\Http\Request;
use App\Libraries\LaravelHttpClient;
use Providers\Pla\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $response = [
            'code' => 200,
            'data' => (object) [
                'url' => 'testUrl.com'
            ]
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
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $response = [
            'code' => 200,
            'data' => (object) [
                'url' => 'testUrl.com'
            ]
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
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'code' => 401,
                'data' => (object) [
                    'url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getGameLaunchUrl(credentials: $providerCredentials, request: $request, token: 'testToken');
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

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'code' => 200,
                'data' => (object) [
                    'url' => 'testUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->getGameLaunchUrl(credentials: $providerCredentials, request: $request, token: 'testToken');

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_gameRoundStatus_mockHttp_get()
    {
        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAdminKey')
            ->willReturn('testAdminKey');

        $providerCredentials->method('getApiUrl')
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
                    'game_history_url' => [
                        'testUrl.com'
                    ]
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->gameRoundStatus(credentials: $providerCredentials, transactionID: 'testTransactionID');
    }

    #[DataProvider('gameRoundStatusParams')]
    public function test_gameRoundStatus_stubHttpMissingResponse_thirdPartyApiErrorException($parameter)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $response = [
            'code' => 200,
            'data' => (object) [
                'game_history_url' => [
                    'testUrl.com'
                ]
            ]
        ];

        if (isset($response[$parameter]) === false)
            unset($response['data']->$parameter);
        else
            unset($response[$parameter]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->gameRoundStatus(credentials: $providerCredentials, transactionID: 'testTransactionID');
    }

    #[DataProvider('gameRoundStatusParams')]
    public function test_gameRoundStatus_stubHttpInvalidResponseDataType_thirdPartyApiErrorException($parameter, $value)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $response = [
            'code' => 200,
            'data' => (object) [
                'game_history_url' => [
                    'testUrl.com'
                ]
            ]
        ];

        if (isset($response[$parameter]) === false)
            $response['data']->$parameter = $value;
        else
            $response[$parameter] = $value;

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $response);

        $api = $this->makeApi(http: $stubHttp);
        $api->gameRoundStatus(credentials: $providerCredentials, transactionID: 'testTransactionID');
    }

    public static function gameRoundStatusParams()
    {
        return [
            ['code', 'invalid'],
            ['data', 'invalid'],
            ['game_history_url', 123]
        ];
    }

    public function test_gameRoundStatus_stubHttpErrorCodeNot200_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'code' => 500,
                'data' => null
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->gameRoundStatus(credentials: $providerCredentials, transactionID: 'testTransactionID');
    }

    public function test_gameRoundStatus_stubHttp_expectedData()
    {
        $expected = 'testUrl.com';

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'code' => 200,
                'data' => (object) [
                    'game_history_url' => [
                        'testUrl.com'
                    ]
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $response = $api->gameRoundStatus(credentials: $providerCredentials, transactionID: 'testTransactionID');

        $this->assertSame(expected: $expected, actual: $response);
    }
}
