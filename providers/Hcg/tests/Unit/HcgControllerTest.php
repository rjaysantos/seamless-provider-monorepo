<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Hcg\HcgService;
use Providers\Hcg\HcgResponse;
use Providers\Hcg\HcgController;
use Providers\Hcg\HcgEncryption;
use Illuminate\Http\JsonResponse;
use Providers\Hcg\HcgCredentials;
use Providers\Hcg\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Hcg\Exceptions\InvalidProviderRequestException;

class HcgControllerTest extends TestCase
{
    private function makeController(
        HcgService $service = null,
        HcgResponse $response = null,
        HcgCredentials $credentials = null,
        HcgEncryption $encryption = null
    ): HcgController {
        $service ??= $this->createStub(HcgService::class);
        $response ??= $this->createStub(HcgResponse::class);
        $credentials ??= $this->createStub(HcgCredentials::class);
        $encryption ??= $this->createStub(HcgEncryption::class);

        return new HcgController(
            service: $service,
            response: $response,
            credentials: $credentials,
            encryption: $encryption
        );
    }

    #[DataProvider('playParams')]
    public function test_play_missingRequestParameter_invalidCasinoRequestException($unset)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestParameterType_invalidCasinoRequestException($key)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $request[$key] = 123;

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public static function playParams()
    {
        return [
            ['playId'],
            ['username'],
            ['currency'],
            ['gameId'],
        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $request->headers->set('Authorization', 'INVALID_BEARER_TOKEN');

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public function test_play_mockService_getLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(HcgService::class);
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
            'gameId' => '1'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testUrl.com');

        $mockResponse = $this->createMock(HcgResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->play(request: $request);
    }

    public function test_play_stubResponse_expected()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $expected = new JsonResponse();

        $stubResponse = $this->createMock(HcgResponse::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->play(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('visualParams')]
    public function test_visual_missingRequestParameter_invalidCasinoRequestException($parameter)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequestParameterType_invalidCasinoRequestException($key)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $request[$key] = 123;

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public static function visualParams()
    {
        return [
            ['play_id'],
            ['bet_id'],
            ['currency']
        ];
    }

    public function test_visual_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'INVALID_BEARER_TOKEN');

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public function test_visual_mockService_getVisualUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(HcgService::class);
        $mockService->expects($this->once())
            ->method('getVisualUrl')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->visual(request: $request);
    }

    public function test_visual_mockResponse_casinoSuccess()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('getVisualUrl')
            ->willReturn('testVisualUrl.com');
        
        $mockResponse = $this->createMock(HcgResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testVisualUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visual(request: $request);
    }

    public function test_visual_stubResponse_expected()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $expected = new JsonResponse();
        
        $stubResponse = $this->createMock(HcgResponse::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->visual(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('entryPointParams')]
    public function test_entryPoint_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 1,
            'sign' => 'testSign'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('entryPointParams')]
    public function test_entryPoint_invalidRequestType_invalidCasinoRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $request[$key] = $value;

        $controller = $this->makeController();
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public static function entryPointParams()
    {
        return [
            ['action', 'invalid'],
            ['sign', 123]
        ];
    }

    public function test_entryPoint_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $mockCredentials = $this->createMock(HcgCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.0);

        $controller = $this->makeController(
            service: $stubService,
            credentials: $mockCredentials,
            encryption: $stubEncryption
        );

        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_entryPoint_mockEncryption_createSignature()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockEncryption = $this->createMock(HcgEncryption::class);
        $mockEncryption->expects($this->once())
            ->method('createSignature')
            ->with(credentials: $providerCredentials, data: $request->all())
            ->willReturn('testSign');

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.0);

        $controller = $this->makeController(
            service: $stubService,
            credentials: $stubCredentials,
            encryption: $mockEncryption
        );

        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_getBalance_missingRequestParameter_invalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 1,
            'sign' => 'testSign'
        ]);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_getBalance_invalidRequestType_invalidCasinoRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 1,
            'sign' => 'testSign'
        ]);

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.0);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController( service: $stubService, encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_getBalance_mockService_getBalance()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $mockService = $this->createMock(HcgService::class);
        $mockService->expects($this->once())
            ->method('getBalance')
            ->with(request: $request)
            ->willReturn(1000.0);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(service: $mockService, encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_getBalance_mockResponse_getBalance()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.0);

        $mockResponse = $this->createMock(HcgResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.0);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse,
            encryption: $stubEncryption
        );

        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_getBalance_stubResponse_expectedData()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $expected = new JsonResponse;

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.0);

        $stubResponse = $this->createMock(HcgResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn($expected);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(service: $stubService, response: $stubResponse, encryption: $stubEncryption);
        $response = $controller->entryPoint(request: $request, currency: 'IDR');

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('settlementParams')]
    public function test_settlement_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        unset($request[$parameter]);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('settlementParams')]
    public function test_settlement_invalidRequestType_invalidCasinoRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $request[$key] = $value; 

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('betAndSettle')
            ->willReturn(1000.0);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(service: $stubService, encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public static function settlementParams()
    {
        return [
            ['uid', 123],
            ['timestamp', 'invalid'],
            ['orderNo', 123],
            ['gameCode', 123],
            ['bet', 'invalid'],
            ['win', 'invalid']
        ];
    }

    public function test_settlement_mockService_settlement()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $mockService = $this->createMock(HcgService::class);
        $mockService->expects($this->once())
            ->method('betAndSettle')
            ->with(request: $request)
            ->willReturn(1000.0);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(service: $mockService, encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_settlement_mockResponse_settlement()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('betAndSettle')
            ->willReturn(1000.0);

        $mockResponse = $this->createMock(HcgResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.0);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse,
            encryption: $stubEncryption
        );

        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_settlement_stubResponse_expectedData()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $expected = new JsonResponse;

        $stubService = $this->createMock(HcgService::class);
        $stubService->method('betAndSettle')
            ->willReturn(1000.0);

        $stubResponse = $this->createMock(HcgResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn($expected);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(service: $stubService, response: $stubResponse, encryption: $stubEncryption);
        $response = $controller->entryPoint(request: $request, currency: 'IDR');

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('cancelSettlementParams')]
    public function test_cancelSettlement_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'orderNo' => 'testTransactionID',
            'sign' => 'testSign'
        ]);

        unset($request[$parameter]);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    #[DataProvider('cancelSettlementParams')]
    public function test_cancelSettlement_invalidRequestType_invalidProviderRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'orderNo' => 'testTransactionID',
            'sign' => 'testSign'
        ]);

        $request[$key] = $value;

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public static function cancelSettlementParams()
    {
        return [
            ['action', 'invalid'],
            ['uid', 123],
            ['orderNo', 123],
            ['sign', 123]
        ];
    }

    public function test_cancelSettlement_mockService_cancelBetAndSettle()
    {
        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'orderNo' => 'testTransactionID',
            'sign' => 'testSign'
        ]);

        $mockService = $this->createMock(HcgService::class);
        $mockService->expects($this->once())
            ->method('cancelBetAndSettle')
            ->with(request: $request);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(service: $mockService, encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_gameOfflineNotification_mockResponse_gameOfflineNotification()
    {
        $request = new Request([
            'action' => 4,
            'sign' => 'testSign'
        ]);

        $mockResponse = $this->createMock(HcgResponse::class);
        $mockResponse->expects($this->once())
            ->method('gameOfflineNotification');

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(response: $mockResponse, encryption: $stubEncryption);
        $controller->entryPoint(request: $request, currency: 'IDR');
    }

    public function test_gameOfflineNotification_stubResponse_expectedData()
    {
        $request = new Request([
            'action' => 4,
            'sign' => 'testSign'
        ]);

        $expected = new JsonResponse;

        $mockResponse = $this->createMock(HcgResponse::class);
        $mockResponse->method('gameOfflineNotification')
            ->willReturn($expected);

        $stubEncryption = $this->createMock(HcgEncryption::class);
        $stubEncryption->method('createSignature')
            ->willReturn('testSign');

        $controller = $this->makeController(response: $mockResponse, encryption: $stubEncryption);
        $result = $controller->entryPoint(request: $request, currency: 'IDR');
        
        $this->assertSame(expected: $expected, actual: $result);
    }
}