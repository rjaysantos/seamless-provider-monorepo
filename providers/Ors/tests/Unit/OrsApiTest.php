<?php

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Ors\OrsApi;
use App\Libraries\LaravelHttpClient;
use Providers\Ors\OgSignature;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Ors\Contracts\ICredentials;
use App\Exceptions\Casino\ThirdPartyApiErrorException;

class OrsApiTest extends TestCase
{
    public function makeApi(LaravelHttpClient $http = null, OgSignature $encryption = null)
    {
        $http ??= $this->createMock(LaravelHttpClient::class);
        $encryption ??= $this->createMock(OgSignature::class);

        return new OrsApi(http: $http, encryption: $encryption);
    }

    public function test_enterGame_mockEncryption_createSignatureByArray()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $token = 'testToken';

        Carbon::setTestNow('2025-01-01 00:00:00');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'game_link' => 'testUrl.com'
            ]);

        $stubCredentials = $this->createMock(ICredentials::class);

        $mockEncryption = $this->createMock(OgSignature::class);
        $mockEncryption->expects($this->once())
            ->method('createSignatureByArray')
            ->with(
                arrayData: [
                    'player_id' => $request->playId,
                    'timestamp' => Carbon::now()->timestamp,
                    'nickname' => $request->playId,
                    'token' => $token,
                    'lang' => $request->language,
                    'game_id' => $request->gameId,
                    'betlimit' => 164,
                ],
                credentials: $stubCredentials
            )
            ->willReturn('testSignature');

        $api = $this->makeApi(http: $stubHttp, encryption: $mockEncryption);
        $api->enterGame(credentials: $stubCredentials, request: $request, token: $token);

        Carbon::setTestNow();
    }

    public function test_enterGame_mockCredentials_getPublicKey()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $token = 'testToken';

        $mockCredentials = $this->createMock(ICredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getPublicKey');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'game_link' => 'testUrl.com'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->enterGame(credentials: $mockCredentials, request: $request, token: $token);
    }

    public function test_enterGame_mockCredentials_getOperatorName()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $token = 'testToken';

        $mockCredentials = $this->createMock(ICredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getOperatorName');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'game_link' => 'testUrl.com'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->enterGame(credentials: $mockCredentials, request: $request, token: $token);
    }

    public function test_enterGame_mockCredentials_getApiUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $token = 'testToken';

        $mockCredentials = $this->createMock(ICredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getApiUrl');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'game_link' => 'testUrl.com'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->enterGame(credentials: $mockCredentials, request: $request, token: $token);
    }

    public function test_enterGame_mockHttp_get()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $token = 'testToken';

        Carbon::setTestNow('2025-01-01 00:00:00');

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('createSignatureByArray')
            ->willReturn('testSignature');

        $stubCredentials = $this->createMock(ICredentials::class);
        $stubCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials->method('getOperatorName')
            ->willReturn('testOperatorName');

        $stubCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('get')
            ->with(
                url: 'testApiUrl.com/api/v2/platform/games/launch',
                request: [
                    'player_id' => $request->playId,
                    'timestamp' => Carbon::now()->timestamp,
                    'nickname' => $request->playId,
                    'token' => $token,
                    'lang' => $request->language,
                    'game_id' => $request->gameId,
                    'betlimit' => 164,
                    'signature' => 'testSignature'
                ],
                headers: [
                    'key' => 'testPublicKey',
                    'operator-name' => 'testOperatorName',
                    'content-type' => 'application/json'
                ]
            )
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'game_link' => 'testUrl.com'
            ]);

        $api = $this->makeApi(http: $mockHttp, encryption: $stubEncryption);
        $api->enterGame(credentials: $stubCredentials, request: $request, token: $token);

        Carbon::setTestNow();
    }

    #[DataProvider('enterGameParams')]
    public function test_enterGame_missingThirdPartyApiResponseParameter_thirdPartyApiErrorException($unset)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $token = 'testToken';

        $apiResponse = [
            'rs_code' => 'S-100',
            'game_link' => 'testUrl.com'
        ];

        unset($apiResponse[$unset]);

        $stubCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->enterGame(credentials: $stubCredentials, request: $request, token: $token);
    }

    #[DataProvider('enterGameParams')]
    public function test_enterGame_invalidThirdPartyApiResponseParameterType_thirdPartyApiErrorException($key)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $token = 'testToken';

        $apiResponse = [
            'rs_code' => 'S-100',
            'game_link' => 'testUrl.com'
        ];

        $apiResponse[$key] = 123;

        $stubCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->enterGame(credentials: $stubCredentials, request: $request, token: $token);
    }

    public static function enterGameParams()
    {
        return [
            ['rs_code'],
            ['game_link']
        ];
    }

    public function test_enterGame_invalidStatusCode_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $token = 'testToken';

        $stubCredentials = $this->createMock(ICredentials::class);
        $stubCredentials->method('getApiUrl');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'invalid'
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->enterGame(credentials: $stubCredentials, request: $request, token: $token);
    }

    public function test_enterGame_stubHttp_expectedData()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $token = 'testToken';

        $expected = 'testUrl.com';

        $stubCredentials = $this->createMock(ICredentials::class);

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'game_link' => $expected
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $response = $api->enterGame(credentials: $stubCredentials, request: $request, token: $token);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_getBettingRecords_mockCredentials_getPublicKey()
    {
        $transactionId = 'testBetID';
        $playId = 'testPlayID';

        $mockCredentials = $this->createMock(ICredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'records' => [
                    (object) [
                        'result_url' => 'testUrl.com'
                    ]
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBettingRecords(credentials: $mockCredentials, transactionID: $transactionId, playID: $playId);
    }

    public function test_getBettingRecords_mockCredentials_getOperatorName()
    {
        $transactionId = 'testBetID';
        $playId = 'testPlayID';

        $mockCredentials = $this->createMock(ICredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getOperatorName')
            ->willReturn('testOperatorName');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'records' => [
                    (object) [
                        'result_url' => 'testUrl.com'
                    ]
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBettingRecords(credentials: $mockCredentials, transactionID: $transactionId, playID: $playId);
    }

    public function test_getBettingRecords_mockCredentials_getApiUrl()
    {
        $transactionId = 'testBetID';
        $playId = 'testPlayID';

        $mockCredentials = $this->createMock(ICredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'records' => [
                    (object) [
                        'result_url' => 'testUrl.com'
                    ]
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBettingRecords(credentials: $mockCredentials, transactionID: $transactionId, playID: $playId);
    }

    public function test_getBettingRecords_mockHttp_get()
    {
        $transactionId = 'testBetID';
        $playId = 'testPlayID';

        $stubCredentials = $this->createMock(ICredentials::class);
        $stubCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials->method('getOperatorName')
            ->willReturn('testOperatorName');

        $stubCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->expects($this->once())
            ->method('get')
            ->with(
                url: 'testApiUrl.com/api/v2/platform/transaction/history',
                request: [
                    'transaction_id' => $transactionId,
                    'player_id' => $playId,
                    'game_type_id' => 2
                ],
                headers: [
                    'key' => 'testPublicKey',
                    'operator-name' => 'testOperatorName',
                    'content-type' => 'application/json'
                ]
            )
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'records' => [
                    (object) [
                        'result_url' => 'testUrl.com'
                    ]
                ]
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getBettingRecords(credentials: $stubCredentials, transactionID: $transactionId, playID: $playId);
    }

    public function test_getBettingRecords_invalidStatusCode_thirdPartyApiErrorException()
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $transactionId = 'testBetID';
        $playId = 'testPlayID';

        $stubCredentials = $this->createMock(ICredentials::class);

        $mockHttp = $this->createMock(LaravelHttpClient::class);
        $mockHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'invalid'
            ]);

        $api = $this->makeApi(http: $mockHttp);
        $api->getBettingRecords(credentials: $stubCredentials, transactionID: $transactionId, playID: $playId);
    }

    #[DataProvider('getBettingRecordsParams')]
    public function test_getBettingRecords_missingThirdPartyApiResponseParameter_thirdPartyApiErrorException($unset)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $transactionId = 'testBetID';
        $playId = 'testPlayID';

        $apiResponse = [
            'rs_code' => 'S-100',
            'records' => [
                (object) [
                    'result_url' => 'testUrl.com'
                ]
            ]
        ];

        if (isset($apiResponse[$unset]) === true)
            unset($apiResponse[$unset]);
        else
            unset($apiResponse['records'][0]->$unset);

        $stubCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBettingRecords(credentials: $stubCredentials, transactionID: $transactionId, playID: $playId);
    }

    #[DataProvider('getBettingRecordsParams')]
    public function test_getBettingRecords_invalidThirdPartyApiResponseParameterType_thirdPartyApiErrorException($key, $value)
    {
        $this->expectException(ThirdPartyApiErrorException::class);

        $transactionId = 'testBetID';
        $playId = 'testPlayID';

        $apiResponse = [
            'rs_code' => 'S-100',
            'records' => [
                (object) [
                    'result_url' => 'testUrl.com'
                ]
            ]
        ];

        if (isset($apiResponse[$key]) === true)
            $apiResponse[$key] = $value;
        else
            $apiResponse['records'][0]->$key = $value;

        $stubCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) $apiResponse);

        $api = $this->makeApi(http: $stubHttp);
        $api->getBettingRecords(credentials: $stubCredentials, transactionID: $transactionId, playID: $playId);
    }

    public static function getBettingRecordsParams()
    {
        return [
            ['rs_code', 123],
            ['records', 'sample'],
            ['result_url', 123]
        ];
    }

    public function test_getBettingRecords_stubHttp_expectedData()
    {
        $transactionId = 'testBetID';
        $playId = 'testPlayID';

        $expected = 'testUrl.com';

        $stubCredentials = $this->createMock(ICredentials::class);

        $stubHttp = $this->createMock(LaravelHttpClient::class);
        $stubHttp->method('get')
            ->willReturn((object) [
                'rs_code' => 'S-100',
                'records' => [
                    (object) [
                        'result_url' => $expected
                    ]
                ]
            ]);

        $api = $this->makeApi(http: $stubHttp);

        $response = $api->getBettingRecords(
            credentials: $stubCredentials,
            transactionID: $transactionId,
            playID: $playId
        );

        $this->assertSame(expected: $expected, actual: $response);
    }
}
