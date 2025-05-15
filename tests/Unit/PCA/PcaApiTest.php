<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\GameProviders\V2\PCA\PcaApi;
use App\Libraries\LaravelHttpClient;
use App\GameProviders\V2\PLA\Contracts\ICredentials;
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
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'PCA',
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
            'gameId' => 'PCA',
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
            'gameId' => 'PCA',
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
}