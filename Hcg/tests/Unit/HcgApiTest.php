<?php

use Tests\TestCase;
use Providers\Hcg\HcgApi;
use App\Libraries\LaravelHttpClient;
use Providers\Hcg\HcgEncryption;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Hcg\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class HcgApiTest extends TestCase
{
    private function makeApi(LaravelHttpClient $http = null, HcgEncryption $encryption = null): HcgApi
    {
        $http ??= $this->createStub(LaravelHttpClient::class);
        $encryption ??= $this->createStub(HcgEncryption::class);

        return new HcgApi(http: $http, encryption: $encryption);
    }

    public function test_userRegistrationInterface_mockEncryption_encrypt()
    {
        $playID = 'testPlayID';

        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getAppID')->willReturn('testAppID');
        $credentials->method('getAppSecret')->willReturn('testAppSecret');

        $mockEncryption = $this->createMock(HcgEncryption::class);
        $mockEncryption->expects($this->once())
            ->method('encrypt')
            ->with(
                credentials: $credentials,
                data: [
                    'action' => 'register',
                    'appID' => 'testAppID',
                    'appSecret' => 'testAppSecret',
                    'uid' => $playID
                ]
            );

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'returnCode' => '0000',
                'returnMsg' => 'Success',
                'data' => []
            ]);

        $api = $this->makeApi(http: $stubHttp, encryption: $mockEncryption);
        $api->userRegistrationInterface(credentials: $credentials, playID: $playID);
    }

    public function test_userRegistrationInterface_mockHttp_post()
    {
        $playID = 'testPlayID';

        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getApiUrl')->willReturn('testApiUrl.com');

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('encrypt')->willReturn('testEncryption');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('post')
            ->with(
                url: 'testApiUrl.com',
                request: [
                    'lang' => 'en',
                    'x' => 'testEncryption'
                ],
                headers: []
            )
            ->willReturn((object) [
                'returnCode' => '0000',
                'returnMsg' => 'Success',
                'data' => []
            ]);

        $api = $this->makeApi(http: $mockHttp, encryption: $stubEncryption);
        $api->userRegistrationInterface(credentials: $credentials, playID: $playID);
    }

    public function test_userRegistrationInterface_missingThirdPartyApiResponseParameter_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $playID = 'testPlayID';

        $credentials = $this->createMock(ICredentials::class);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('encrypt')->willReturn('testEncryption');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('post')
            ->willReturn((object) [
                'returnMsg' => 'Success',
                'data' => []
            ]);

        $api = $this->makeApi(http: $mockHttp, encryption: $stubEncryption);
        $api->userRegistrationInterface(credentials: $credentials, playID: $playID);
    }

    public function test_userRegistrationInterface_invalidThirdPartyApiResponseParameterType_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $playID = 'testPlayID';

        $credentials = $this->createMock(ICredentials::class);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('encrypt')->willReturn('testEncryption');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('post')
            ->willReturn((object) [
                'returnCode' => 1234,
                'returnMsg' => 'Success',
                'data' => []
            ]);

        $api = $this->makeApi(http: $mockHttp, encryption: $stubEncryption);
        $api->userRegistrationInterface(credentials: $credentials, playID: $playID);
    }

    public function test_userLoginInterface_mockEncryption_encrypt()
    {
        $playID = 'testPlayID';
        $gameCode = 'testGameCode';

        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getAppID')->willReturn('testAppID');
        $credentials->method('getAppSecret')->willReturn('testAppSecret');

        $mockEncryption = $this->createMock(HcgEncryption::class);
        $mockEncryption->expects($this->once())
            ->method('encrypt')
            ->with(
                credentials: $credentials,
                data: [
                    'action' => 'login',
                    'appID' => 'testAppID',
                    'appSecret' => 'testAppSecret',
                    'uid' => $playID,
                    'gameCode' => $gameCode
                ]
            );

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('post')
            ->willReturn((object) [
                'returnCode' => '0000',
                'returnMsg' => 'Success',
                'data' => (object) [
                    'path' => 'testLaunchUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp, encryption: $mockEncryption);
        $api->userLoginInterface(credentials: $credentials, playID: $playID, gameCode: $gameCode);
    }

    public function test_userLoginInterface_mockHttp_post()
    {
        $playID = 'testPlayID';
        $gameCode = 'testGameCode';

        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getApiUrl')->willReturn('testApiUrl.com');

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('encrypt')->willReturn('testEncryption');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('post')
            ->with(
                url: 'testApiUrl.com',
                request: [
                    'lang' => 'en',
                    'x' => 'testEncryption'
                ],
                headers: []
            )
            ->willReturn((object) [
                'returnCode' => '0000',
                'returnMsg' => 'Success',
                'data' => (object) [
                    'path' => 'testLaunchUrl.com'
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp, encryption: $stubEncryption);
        $api->userLoginInterface(credentials: $credentials, playID: $playID, gameCode: $gameCode);
    }

    #[DataProvider('userLoginInterfaceResponseParams')]
    public function test_userLoginInterface_missingThirdPartyApiResponseParameter_thirdPartyApiErrorException($unset)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $response = [
            'returnCode' => '0000',
            'returnMsg' => 'Success',
            'data' => [
                'path' => 'testVisualUrl.com'
            ]
        ];

        if (isset($response[$unset]) === true)
            unset($response[$unset]);
        else
            unset($response['data'][$unset]);

        $playID = 'testPlayID';
        $gameCode = 'testGameCode';

        $credentials = $this->createMock(ICredentials::class);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('encrypt')->willReturn('testEncryption');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('post')->willReturn((object) $response);

        $api = $this->makeApi(http: $mockHttp, encryption: $stubEncryption);
        $api->userLoginInterface(credentials: $credentials, playID: $playID, gameCode: $gameCode);
    }

    #[DataProvider('userLoginInterfaceResponseParams')]
    public function test_userLoginInterface_invalidThirdPartyApiResponseParameterType_thirdPartyApiErrorException($key)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $response = [
            'returnCode' => '0000',
            'returnMsg' => 'Success',
            'data' => [
                'path' => 'testVisualUrl.com'
            ]
        ];

        if (isset($response[$key]) === true)
            $response[$key] = 123;
        else
            $response['data'][$key] = 123;

        $playID = 'testPlayID';
        $gameCode = 'testGameCode';

        $credentials = $this->createMock(ICredentials::class);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('encrypt')->willReturn('testEncryption');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('post')->willReturn((object) $response);

        $api = $this->makeApi(http: $mockHttp, encryption: $stubEncryption);
        $api->userLoginInterface(credentials: $credentials, playID: $playID, gameCode: $gameCode);
    }

    public static function userLoginInterfaceResponseParams()
    {
        return [
            ['returnCode'],
            ['data'],
            ['path']
        ];
    }
}