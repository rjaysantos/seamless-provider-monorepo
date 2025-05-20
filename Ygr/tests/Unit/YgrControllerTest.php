<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Ygr\YgrService;
use Providers\Ygr\YgrResponse;
use Providers\Ygr\YgrController;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Ygr\Exceptions\InvalidProviderRequestException;

class YgrControllerTest extends TestCase
{
    private function makeController(
        $service = null,
        $response = null
    ): YgrController {
        $service ??= $this->createStub(YgrService::class);
        $response ??= $this->createStub(YgrResponse::class);

        return new YgrController(
            service: $service,
            response: $response
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
            'gameId' => 'testGameID',
            'language' => 'id'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestType_invalidCasinoRequestException($parameter)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ]);

        $request[$parameter] = 123;

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
            ['language']
        ];
    }

    public function test_play_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . 'invalidBearerToken');

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public function test_play_mockCasinoService_getLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockCasinoService = $this->createMock(YgrService::class);
        $mockCasinoService->expects($this->once())
            ->method('getLaunchUrl')
            ->with($request);

        $controller = $this->makeController(service: $mockCasinoService);
        $controller->play(request: $request);
    }

    public function test_play_mockResponse_casinoSuccess()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(YgrService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('TestUrl.com');

        $mockResponse = $this->createMock(YgrResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with('TestUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->play(request: $request);
    }

    public function test_play_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'id'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubResponse = $this->createMock(YgrResponse::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
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
            'txn_id' => null,
            'currency' => 'IDR'
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
            'txn_id' => null,
            'currency' => 'IDR'
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
            ['currency']
        ];
    }

    public function test_visual_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . 'invalidBearerToken');

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public function test_visual_mockCasinoService_getLaunchUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockCasinoService = $this->createMock(YgrService::class);
        $mockCasinoService->expects($this->once())
            ->method('getBetDetail')
            ->with($request);

        $controller = $this->makeController(service: $mockCasinoService);
        $controller->visual(request: $request);
    }

    public function test_visual_mockResponse_casinoSuccess()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(YgrService::class);
        $stubService->method('getBetDetail')
            ->willReturn('visualUrl.com');

        $mockResponse = $this->createMock(YgrResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with('visualUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visual(request: $request);
    }

    public function test_visual_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubResponse = $this->createMock(YgrResponse::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->visual(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_verifyToken_invalidRequestType_invalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['connectToken' => 123456789]);

        $controller = $this->makeController();
        $controller->verifyToken(request: $request);
    }

    public function test_verifyToken_mockProviderService_getPlayerDetails()
    {
        $request = new Request(['connectToken' => 'testToken']);

        $mockProviderService = $this->createMock(YgrService::class);
        $mockProviderService->expects($this->once())
            ->method('getPlayerDetails')
            ->with($request);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->verifyToken(request: $request);
    }

    public function test_verifyToken_mockResponse_verifyToken()
    {
        $request = new Request(['connectToken' => 'testToken']);

        $stubProviderService = $this->createMock(YgrService::class);
        $stubProviderService->method('getPlayerDetails')
            ->willReturn((object) []);

        $mockResponse = $this->createMock(YgrResponse::class);
        $mockResponse->expects($this->once())
            ->method('verifyToken')
            ->with((object) []);

        $controller = $this->makeController(service: $stubProviderService, response: $mockResponse);
        $controller->verifyToken(request: $request);
    }

    public function test_verifyToken_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request(['connectToken' => 'testToken']);

        $stubResponse = $this->createMock(YgrResponse::class);
        $stubResponse->method('verifyToken')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->verifyToken(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_getBalance_invalidRequestType_invalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['connectToken' => 123456789]);

        $controller = $this->makeController();
        $controller->getBalance(request: $request);
    }

    public function test_getBalance_mockProviderService_authorizationConnectToken()
    {
        $request = new Request(['connectToken' => 'testToken']);

        $mockProviderService = $this->createMock(YgrService::class);
        $mockProviderService->expects($this->once())
            ->method('getPlayerDetails')
            ->with($request);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->getBalance(request: $request);
    }

    public function test_getBalance_mockResponse_verifyToken()
    {
        $request = new Request(['connectToken' => 'testToken']);

        $stubProviderService = $this->createMock(YgrService::class);
        $stubProviderService->method('getPlayerDetails')
            ->willReturn((object) [
                'currency' => 'IDR',
                'balance' => 100.00
            ]);

        $mockResponse = $this->createMock(YgrResponse::class);
        $mockResponse->expects($this->once())
            ->method('getBalance')
            ->with((object) [
                'currency' => 'IDR',
                'balance' => 100.00
            ]);

        $controller = $this->makeController(service: $stubProviderService, response: $mockResponse);
        $controller->getBalance(request: $request);
    }

    public function test_getBalance_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request(['connectToken' => 'testToken']);

        $stubResponse = $this->createMock(YgrResponse::class);
        $stubResponse->method('getBalance')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->getBalance(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_deleteToken_missingRequestParameter_invalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        unset($request['connectToken']);

        $controller = $this->makeController();
        $controller->deleteToken(request: $request);
    }

    public function test_deleteToken_invalidRequestType_invalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $request['connectToken'] = 123;

        $controller = $this->makeController();
        $controller->deleteToken(request: $request);
    }

    public function test_deleteToken_mockProviderService_deleteToken()
    {
        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $mockProviderService = $this->createMock(YgrService::class);
        $mockProviderService->expects($this->once())
            ->method('deleteToken')
            ->with($request);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->deleteToken(request: $request);
    }

    public function test_deleteToken_mockResponse_deleteToken()
    {
        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $mockResponse = $this->createMock(YgrResponse::class);
        $mockResponse->expects($this->once())
            ->method('deleteToken')
            ->with();

        $controller = $this->makeController(response: $mockResponse);
        $controller->deleteToken(request: $request);
    }

    public function test_deleteToken_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $stubResponse = $this->createMock(YgrResponse::class);
        $stubResponse->method('deleteToken')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->deleteToken(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('betAndSettleParams')]
    public function test_betAndSettle_invalidRequestType_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->betAndSettle(request: $request);
    }

    #[DataProvider('betAndSettleParams')]
    public function test_betAndSettle_missingRequestParameter_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->betAndSettle(request: $request);
    }

    public static function betAndSettleParams()
    {
        return [
            ['connectToken', 123],
            ['roundID', 123],
            ['betAmount', 'zero'],
            ['payoutAmount', 'zero'],
            ['freeGame', 'free'],
            ['wagersTime', 12312024]
        ];
    }

    public function test_betAndSettle_mockProviderService_betAndSettle()
    {
        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $mockProviderService = $this->createMock(YgrService::class);
        $mockProviderService->expects($this->once())
            ->method('betAndSettle')
            ->with($request);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockResponse_betAndSettle()
    {
        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubProviderService = $this->createMock(YgrService::class);
        $stubProviderService->method('betAndSettle')
            ->willReturn((object) []);

        $mockResponse = $this->createMock(YgrResponse::class);
        $mockResponse->expects($this->once())
            ->method('betAndSettle')
            ->with((object) []);

        $controller = $this->makeController(service: $stubProviderService, response: $mockResponse);
        $controller->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubResponse = $this->createMock(YgrResponse::class);
        $stubResponse->method('betAndSettle')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->betAndSettle(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }
}