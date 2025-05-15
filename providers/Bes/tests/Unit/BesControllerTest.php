<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Bes\BesService;
use Providers\Bes\BesResponse;
use Providers\Bes\BesController;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Bes\Exceptions\InvalidProviderRequestException;

class BesControllerTest extends TestCase
{
    private function makeController($service = null, $response = null): BesController
    {
        $service ??= $this->createStub(BesService::class);
        $response ??= $this->createStub(BesResponse::class);

        return new BesController(service: $service, response: $response);
    }

    #[DataProvider('visualParams')]
    public function test_visual_missingRequestParameter_invalidCasinoRequestException($param)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        unset($request[$param]);

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequestType_invalidCasinoRequestException($param)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $request[$param] = 123;

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public static function visualParams()
    {
        return [
            ['play_id'],
            ['bet_id'],
            ['currency'],
        ];
    }

    public function test_visual_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $request->headers->set('Authorization', 'Bearer invalidBearerToken');

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public function test_visual_mockService_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(BesService::class);
        $mockService->expects($this->once())
            ->method('getBetDetailUrl')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->visual(request: $request);
    }

    public function test_visual_mockResponse_casinoResponse()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(BesService::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('testVisualUrl.com');

        $mockResponse = $this->createMock(BesResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoResponse')
            ->with(data: 'testVisualUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visual(request: $request);
    }

    public function test_visual_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubResponse = $this->createMock(BesResponse::class);
        $stubResponse->method('casinoResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->visual(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('playParams')]
    public function test_play_missingRequestParameter_invalidCasinoRequestException($param)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        unset($request[$param]);

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestType_invalidCasinoRequestException($param)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        $request[$param] = 123;

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
            ['language'],
            ['host']
        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        $request->headers->set('Authorization', 'Bearer invalidBearerToken');

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public function test_play_mockService_getLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(BesService::class);
        $mockService->expects($this->once())
            ->method('getLaunchUrl')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->play(request: $request);
    }

    public function test_play_mockResponse_casinoResponse()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(BesService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testLaunchUrl.com');

        $mockResponse = $this->createMock(BesResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoResponse')
            ->with(data: 'testLaunchUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->play(request: $request);
    }

    public function test_play_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubResponse = $this->createMock(BesResponse::class);
        $stubResponse->method('casinoResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->play(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_updateGamePosition_mockResponse_casinoResponse()
    {
        $mockResponse = $this->createMock(BesResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoResponse')
            ->with(data: 'Success');

        $controller = $this->makeController(response: $mockResponse);
        $controller->updateGamePosition();
    }

    public function test_updateGamePosition_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $stubResponse = $this->createMock(BesResponse::class);
        $stubResponse->method('casinoResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->updateGamePosition();

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('requestParams')]
    public function test_entryPoint_invalidRequestType_InvalidProviderRequestException($params, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 1,
        ]);

        $request[$params] = $value;

        $controller = $this->makeController();
        $controller->entryPoint(request: $request);
    }

    #[DataProvider('requestParams')]
    public function test_entryPoint_missingRequestParameter_InvalidProviderRequestException($params)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 1,
        ]);

        unset($request[$params]);

        $controller = $this->makeController();
        $controller->entryPoint(request: $request);
    }

    public static function requestParams()
    {
        return [
            ['action', null],
            ['action', ''],
            ['action', 'test']
        ];
    }

    #[DataProvider('balanceParams')]
    public function test_getBalance_missingRequestParameter_InvalidProviderRequestException($param)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        unset($request[$param]);

        $controller = $this->makeController();
        $controller->entryPoint(request: $request);
    }

    #[DataProvider('balanceParams')]
    public function test_getBalance_invalidRequestType_InvalidProviderRequestException($param, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request[$param] = $value;

        $controller = $this->makeController();
        $controller->entryPoint(request: $request);
    }

    public static function balanceParams()
    {
        return [
            ['action', 'test'],
            ['uid', 123],
            ['currency', 123]
        ];
    }

    public function test_getBalance_mockService_getBalance()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $mockService = $this->createMock(BesService::class);
        $mockService->expects($this->once())
            ->method('getBalance')
            ->with(request: $request)
            ->willReturn(1000.0);

        $controller = $this->makeController(service: $mockService);
        $controller->entryPoint(request: $request);
    }
    
    public function test_getBalance_mockReponse_balance()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $stubService = $this->createMock(BesService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.0);

        $mockResponse = $this->createMock(BesResponse::class);
        $mockResponse->expects($this->once())
            ->method('balance')
            ->with(
                action: $request->action,
                currency: $request->currency,
                balance: 1000.0
            );

        $controller = $this->makeController(response: $mockResponse, service: $stubService);
        $controller->entryPoint(request: $request);
    }

    public function test_getBalance_stubResponse_balance()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $expected = new JsonResponse();

        $stubService = $this->createMock(BesService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.0);

        $stubResponse = $this->createMock(BesResponse::class);
        $stubResponse->method('balance')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubService, response: $stubResponse);
        $response = $controller->entryPoint(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('settleBetRequestParams')]
    public function test_settleBet_missingRequestParameter_InvalidProviderRequestException($params)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 3,
            'uid' => 'test-player-1',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ]);

        unset($request[$params]);

        $controller = $this->makeController();
        $controller->entryPoint(request: $request);
    }

    #[DataProvider('settleBetRequestParams')]
    public function test_settleBet_invalidRequestParams_InvalidProviderRequestException($params, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ]);

        $request[$params] = $value;

        $controller = $this->makeController();
        $controller->entryPoint(request: $request);
    }

    public static function settleBetRequestParams(): array
    {
        return [
            ['action', 'test'],
            ['uid', 123],
            ['mode', 'test'],
            ['gid', 1],
            ['bet', 'test'],
            ['win', 'test'],
            ['ts', 'test'],
            ['roundId', 12345],
            ['transId', 6789],
        ];
    }

    public function test_settleBet_mockService_settleBet()
    {
        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ]);

        $mockService = $this->createMock(BesService::class);
        $mockService->expects($this->once())
            ->method('settleBet')
            ->with(request: $request)
            ->willReturn((object) [
                'balance' => 1000.0,
                'currency' => 'IDR',
            ]);

        $controller = $this->makeController(service: $mockService);
        $controller->entryPoint(request: $request);
    }

    public function test_settleBet_mockResponse_balance()
    {
        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ]);

        $stubService = $this->createMock(BesService::class);
        $stubService->method('settleBet')
            ->willReturn((object) [
                'balance' => 1000.0,
                'currency' => 'IDR',
            ]);

        $mockResponse = $this->createMock(BesResponse::class);
        $mockResponse->expects($this->once())
            ->method('balance')
            ->with(
                action: $request->action,
                currency: 'IDR',
                balance: 1000.0
            );

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->entryPoint(request: $request);
    }

    public function test_settleBet_stubResponse_expected()
    {
        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'mode' => 0,
            'gid' => '1',
            'bet' => 100.0,
            'win' => 10,
            'ts' => 1704038400,
            'roundId' => '12345',
            'transId' => '6789',
        ]);

        $expected = new JsonResponse;

        $stubService = $this->createMock(BesService::class);
        $stubService->method('settleBet')
            ->willReturn((object) [
                'balance' => 1000.0,
                'currency' => 'IDR',
            ]);

        $stubResponse = $this->createMock(BesResponse::class);
        $stubResponse->method('balance')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubService, response: $stubResponse);
        $result = $controller->entryPoint(request: $request);
        
        $this->assertEquals(expected: $expected, actual: $result);
    }
}