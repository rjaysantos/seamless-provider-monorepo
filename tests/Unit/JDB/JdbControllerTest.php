<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\GameProviders\V2\Jdb\JdbService;
use App\GameProviders\V2\Jdb\JdbResponse;
use App\GameProviders\V2\Jdb\JdbController;
use App\GameProviders\V2\Jdb\JdbEncryption;
use App\GameProviders\V2\Jdb\JdbCredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\GameProviders\V2\Jdb\Contracts\ICredentials;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use App\GameProviders\V2\Jdb\Exceptions\InvalidActionException;
use App\GameProviders\V2\Jdb\Exceptions\InvalidProviderRequestException;

class JdbControllerTest extends TestCase
{
    private function makeController(
        $service = null,
        $response = null,
        $credentials = null,
        $encryption = null
    ): JdbController {
        $service ??= $this->createStub(JdbService::class);
        $response ??= $this->createStub(JdbResponse::class);
        $credentials ??= $this->createStub(JdbCredentials::class);
        $encryption ??= $this->createStub(JdbEncryption::class);

        return new JdbController(
            service: $service,
            response: $response,
            credentials: $credentials,
            encryption: $encryption
        );
    }

    #[DataProvider('playParams')]
    public function test_play_missingRequestParameter_invalidCasinoRequestException($parameter)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestType_invalidCasinoRequestException($parameter, $data)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public static function playParams()
    {
        return [
            ['playId', 123],
            ['username', 123],
            ['currency', 123],
            ['language', 123],
            ['device', 'test'],
            ['gameId', 123]
        ];
    }

    public function test_play_mockService_getLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(JdbService::class);
        $mockService->expects($this->once())
            ->method('getLaunchUrl')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->play(request: $request);
    }

    public function test_play_mockResponse_casinoSuccess()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testLaunchUrl.com');

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testLaunchUrl.com');

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse
        );
        $controller->play(request: $request);
    }

    public function test_play_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $mockResponse);
        $response = $controller->play(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('visualParams')]
    public function test_visual_missingRequestParameter_invalidCasinoRequestException($parameter)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequestType_invalidCasinoRequestException($parameter)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);

        $request[$parameter] = 123;

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public static function visualParams()
    {
        return [
            ['play_id'],
            ['bet_id'],
            ['currency'],
            ['game_id']
        ];
    }

    public function test_visual_mockService_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(JdbService::class);
        $mockService->expects($this->once())
            ->method('getBetDetailUrl')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->visual(request: $request);
    }

    public function test_visual_mockResponse_casinoSuccess()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('testLaunchUrl.com');

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testLaunchUrl.com');

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse
        );
        $controller->visual(request: $request);
    }

    public function test_visual_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $mockResponse);
        $response = $controller->visual(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_entrypoint_missingRequest_invalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        unset($request['x']);

        $controller = $this->makeController();
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_entrypoint_invalidRequestType_invalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 123456789]);

        $controller = $this->makeController();
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_entrypoint_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $mockCredentials = $this->createMock(JdbCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 6,
                'uid' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.00);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $stubService,
            credentials: $mockCredentials
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_entrypoint_mockEncryption_decrypt()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockEncryption = $this->createMock(JdbEncryption::class);
        $mockEncryption->expects($this->once())
            ->method('decrypt')
            ->with(
                credentials: $stubProviderCredentials,
                data: $request->x
            )
            ->willReturn((object) [
                'action' => 6,
                'uid' => 'testPlayID',
                'currency' => 'testPlayID'
            ]);

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.00);

        $controller = $this->makeController(
            encryption: $mockEncryption,
            service: $stubService,
            credentials: $stubCredentials
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_entrypoint_stubEncryptionInvalidAction_invalidActionException()
    {
        $this->expectException(InvalidActionException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) ['action' => 987654321]);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            credentials: $stubCredentials
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('balanceParams')]
    public function test_balance_missingRequest_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 6,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        unset($requestData[$parameter]);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('balanceParams')]
    public function test_balance_invalidRequestType_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 6,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $requestData[$parameter] = 123;

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public static function balanceParams()
    {
        return [
            ['uid'],
            ['currency']
        ];
    }

    public function test_balance_mockService_getBalance()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 6,
                'uid' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockService = $this->createMock(JdbService::class);
        $mockService->expects($this->once())
            ->method('getBalance')
            ->with(request: (object) [
                'action' => 6,
                'uid' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $mockService,
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_balance_mockResponse_providerSuccess()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 6,
                'uid' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $stubService,
            response: $mockResponse
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_balance_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 6,
                'uid' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.00);

        $stubResponse = $this->createMock(JdbResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $stubService,
            response: $stubResponse
        );
        $response = $controller->entryPoint(request: $request, currency: 'IDR');

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('cancelBetAndSettleParams')]
    public function test_cancelBetAndSettle_missingRequest_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 4,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID'
        ];

        unset($requestData[$parameter]);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('cancelBetAndSettleParams')]
    public function test_cancelBetAndSettle_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 4,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID'
        ];

        $requestData[$parameter] = $data;

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public static function cancelBetAndSettleParams()
    {
        return [
            ['ts', 'test'],
            ['transferId', 'test'],
            ['uid', 123]
        ];
    }

    public function test_cancelBetAndSettle_mockService_cancelBetAndSettle()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = (object) [
            'action' => 4,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn($requestData);

        $stubService = $this->createMock(JdbService::class);
        $stubService->expects($this->once())
            ->method('cancelBetAndSettle')
            ->with(request: $requestData);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $stubService
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_cancelBetAndSettle_mockResponse_providerSuccess()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 4,
                'ts' => 1609430400000,
                'transferId' => 123456,
                'uid' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('cancelBetAndSettle')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $stubService,
            response: $mockResponse
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_cancelBetAndSettle_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 4,
                'ts' => 1609430400000,
                'transferId' => 123456,
                'uid' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubResponse = $this->createMock(JdbResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            response: $stubResponse
        );
        $response = $controller->entryPoint(request: $request, currency: 'IDR');

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('betAndSettleParams')]
    public function test_betAndSettle_missingRequest_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        unset($requestData[$parameter]);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('betAndSettleParams')]
    public function test_betAndSettle_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $requestData[$parameter] = $data;

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public static function betAndSettleParams()
    {
        return [
            ['ts', 'invalidTimestamp'],
            ['transferId', 'invalidTransferID'],
            ['uid', 123],
            ['currency', 123],
            ['gType', 'invalidGType'],
            ['mType', 'invalidMType'],
            ['bet', 'invalidBet'],
            ['win', 'invalidWin'],
            ['historyId', 123]
        ];
    }

    public function test_betAndSettle_mockService_betAndSettle()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $mockService = $this->createMock(JdbService::class);
        $mockService->expects($this->once())
            ->method('betAndSettle')
            ->with(request: (object) $requestData);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $mockService,
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_betAndSettle_mockResponse_providerSuccess()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 8,
                'ts' => 1609430400000,
                'transferId' => 123456,
                'uid' => 'testPlayID',
                'currency' => 'IDR',
                'gType' => 0,
                'mType' => 1,
                'bet' => -200,
                'win' => 300,
                'historyId' => 'testHistoryID'
            ]);

        $mockService = $this->createMock(JdbService::class);
        $mockService->expects($this->once())
            ->method('betAndSettle')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $mockService,
            response: $mockResponse
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_betAndSettle_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 8,
                'ts' => 1609430400000,
                'transferId' => 123456,
                'uid' => 'testPlayID',
                'currency' => 'IDR',
                'gType' => 0,
                'mType' => 1,
                'bet' => -200,
                'win' => 300,
                'historyId' => 'testHistoryID'
            ]);

        $mockService = $this->createMock(JdbService::class);
        $mockService->expects($this->once())
            ->method('betAndSettle')
            ->willReturn(1000.00);

        $stubResponse = $this->createMock(JdbResponse::class);
        $stubResponse->expects($this->once())
            ->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $mockService,
            response: $stubResponse
        );
        $response = $controller->entryPoint(request: $request, currency: 'IDR');

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('betParams')]
    public function test_bet_missingRequest_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 9,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        unset($requestData[$parameter]);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('betParams')]
    public function test_bet_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 9,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $requestData[$parameter] = $data;

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public static function betParams()
    {
        return [
            ['ts', 'test'],
            ['transferId', 'test'],
            ['uid', 123],
            ['currency', 123],
            ['amount', 'test'],
            ['gType', 'test'],
            ['mType', 'test']
        ];
    }

    public function test_bet_mockService_bet()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = (object) [
            'action' => 9,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn($requestData);

        $mockService = $this->createMock(JdbService::class);
        $mockService->expects($this->once())
            ->method('bet')
            ->with(request: $requestData);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $mockService,
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_bet_mockResponse_providerSuccess()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 9,
                'ts' => 1609430400000,
                'transferId' => 123456,
                'uid' => 'testPlayID',
                'currency' => 'IDR',
                'amount' => 100,
                'gType' => 9,
                'mType' => 123
            ]);

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('bet')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $stubService,
            response: $mockResponse
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_bet_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 9,
                'ts' => 1609430400000,
                'transferId' => 123456,
                'uid' => 'testPlayID',
                'currency' => 'IDR',
                'amount' => 100,
                'gType' => 9,
                'mType' => 123
            ]);

        $stubResponse = $this->createMock(JdbResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            response: $stubResponse
        );
        $response = $controller->entryPoint(request: $request, currency: 'IDR');

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('cancelBetParams')]
    public function test_cancelBet_missingRequest_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 11,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        unset($requestData[$parameter]);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('cancelBetParams')]
    public function test_cancelBet_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 11,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $requestData[$parameter] = $data;

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public static function cancelBetParams()
    {
        return [
            ['ts', 'test'],
            ['uid', 123],
            ['amount', 'test'],
            ['refTransferIds', 'test']
        ];
    }

    public function test_cancelBet_mockService_cancelBet()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = (object) [
            'action' => 11,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn($requestData);

        $mockService = $this->createMock(JdbService::class);
        $mockService->expects($this->once())
            ->method('cancelBet')
            ->with(request: $requestData);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $mockService,
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_cancelBet_mockResponse_providerSuccess()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 11,
                'ts' => 1609430400000,
                'uid' => 'testPlayID',
                'currency' => 'IDR',
                'amount' => 100,
                'refTransferIds' => [123456]
            ]);

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('cancelBet')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $stubService,
            response: $mockResponse
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_cancelBet_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 11,
                'ts' => 1609430400000,
                'uid' => 'testPlayID',
                'currency' => 'IDR',
                'amount' => 100,
                'refTransferIds' => [123456]
            ]);

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('cancelBet')
            ->willReturn(1000.00);

        $stubResponse = $this->createMock(JdbResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $stubService,
            response: $stubResponse
        );
        $response = $controller->entryPoint(request: $request, currency: 'IDR');

        $this->assertEquals(expected: $expected, actual: $response);
    }


    #[DataProvider('settleParams')]
    public function test_settle_missingRequest_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 10,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        unset($requestData[$parameter]);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('settleParams')]
    public function test_settle_invalidRequest_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = [
            'action' => 10,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $requestData[$parameter] = $data;

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) $requestData);

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public static function settleParams()
    {
        return [
            ['ts', 'test'],
            ['uid', 123],
            ['currency', 123],
            ['amount', 'test'],
            ['refTransferIds', 'test'],
            ['historyId', 123],
            ['gType', 'test'],
            ['mType', 'test']
        ];
    }

    public function test_settle_mockService_settle()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $requestData = (object) [
            'action' => 10,
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn($requestData);

        $mockService = $this->createMock(JdbService::class);
        $mockService->expects($this->once())
            ->method('settle')
            ->with(request: $requestData);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $mockService,
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_settle_mockResponse_providerSuccess()
    {
        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 10,
                'ts' => 1609430400000,
                'uid' => 'testPlayID',
                'currency' => 'IDR',
                'amount' => 100,
                'refTransferIds' => [123456],
                'historyId' => 'testHistoryID',
                'gType' => 7,
                'mType' => 123
            ]);

        $stubService = $this->createMock(JdbService::class);
        $stubService->method('settle')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(JdbResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            service: $stubService,
            response: $mockResponse
        );
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_settle_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request(['x' => 'testEncryptedRequest']);

        $stubEncryption = $this->createMock(JdbEncryption::class);
        $stubEncryption->method('decrypt')
            ->willReturn((object) [
                'action' => 10,
                'ts' => 1609430400000,
                'uid' => 'testPlayID',
                'currency' => 'IDR',
                'amount' => 100,
                'refTransferIds' => [123456],
                'historyId' => 'testHistoryID',
                'gType' => 7,
                'mType' => 123
            ]);

        $stubResponse = $this->createMock(JdbResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(
            encryption: $stubEncryption,
            response: $stubResponse
        );
        $response = $controller->entryPoint(request: $request, currency: 'IDR');

        $this->assertEquals(expected: $expected, actual: $response);
    }
}
