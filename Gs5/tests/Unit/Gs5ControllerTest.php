<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Gs5\Gs5Service;
use Providers\Gs5\Gs5Response;
use Providers\Gs5\Gs5Controller;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Gs5\Exceptions\InvalidProviderRequestException;

class Gs5ControllerTest extends TestCase
{
    private function makeController($service = null, $response = null): Gs5Controller
    {
        $service ??= $this->createStub(Gs5Service::class);
        $response ??= $this->createStub(Gs5Response::class);

        return new Gs5Controller(service: $service, response: $response);
    }

    public function test_balance_missingRequest_InvalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([]);

        $controller = $this->makeController();
        $controller->balance(request: $request);
    }

    public function test_balance_invalidRequestType_InvalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['access_token' => 123456789]);

        $controller = $this->makeController();
        $controller->balance(request: $request);
    }

    public function test_balance_mockService_getBalance()
    {
        $request = new Request(['access_token' => 'testToken']);

        $mockProviderService = $this->createMock(Gs5Service::class);
        $mockProviderService->expects($this->once())
            ->method('getBalance')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->balance(request: $request);
    }

    public function test_balance_mockResponse_successTransaction()
    {
        $request = new Request(['access_token' => 'testToken']);

        $stubProviderService = $this->createMock(Gs5Service::class);
        $stubProviderService->method('getBalance')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(Gs5Response::class);
        $mockResponse->expects($this->once())
            ->method('successTransaction')
            ->with(balance: 100.00);

        $controller = $this->makeController(service: $stubProviderService, response: $mockResponse);
        $controller->balance(request: $request);
    }

    public function test_balance_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request(['access_token' => 'testToken']);

        $stubResponse = $this->createMock(Gs5Response::class);
        $stubResponse->method('successTransaction')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->balance(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_authenticate_missingRequest_InvalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([]);

        $controller = $this->makeController();
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_invalidRequestType_InvalidProviderRequestException()
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request(['access_token' => 123456789]);

        $controller = $this->makeController();
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_mockService_authenticate()
    {
        $request = new Request(['access_token' => 'testToken']);

        $mockProviderService = $this->createMock(Gs5Service::class);
        $mockProviderService->expects($this->once())
            ->method('authenticate')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_mockResponse_authenticate()
    {
        $request = new Request(['access_token' => 'testToken']);

        $stubProviderService = $this->createMock(Gs5Service::class);
        $stubProviderService->method('authenticate')
            ->willReturn((object) [
                'member_id' => 'testPlayID',
                'member_name' => 'testUsername',
                'balance' => 1000.00
            ]);

        $mockResponse = $this->createMock(Gs5Response::class);
        $mockResponse->expects($this->once())
            ->method('authenticate')
            ->with(data: (object) [
                'member_id' => 'testPlayID',
                'member_name' => 'testUsername',
                'balance' => 1000.00
            ]);

        $controller = $this->makeController(service: $stubProviderService, response: $mockResponse);
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request(['access_token' => 'testToken']);

        $stubResponse = $this->createMock(Gs5Response::class);
        $stubResponse->method('authenticate')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->authenticate(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('refundParams')]
    public function test_refund_invalidRequest_invalidProviderRequestException($param, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '123'
        ]);

        $request[$param] = $value;

        $controller = $this->makeController();
        $controller->refund(request: $request);
    }

    public static function refundParams()
    {
        return [
            ['access_token', 123],
            ['access_token', null],
            ['txn_id', 123],
            ['txn_id', null],
        ];
    }

    public function test_refund_mockService_cancel()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '123'
        ]);

        $mockService = $this->createMock(Gs5Service::class);
        $mockService->expects($this->once())
            ->method('cancel')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->refund(request: $request);
    }

    public function test_refund_mockResponse_successTransaction()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '123'
        ]);

        $stubService = $this->createMock(Gs5Service::class);
        $stubService->method('cancel')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(Gs5Response::class);
        $mockResponse->expects($this->once())
            ->method('successTransaction')
            ->with(balance: 1000.00);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->refund(request: $request);
    }

    public function test_refund_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '123'
        ]);

        $stubResponse = $this->createMock(Gs5Response::class);
        $stubResponse->method('successTransaction')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $result = $controller->refund(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestType_invalidCasinoRequestException($param, $value)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
        ]);

        $request[$param] = $value;

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public static function playParams()
    {
        return [
            ['playId', 123],
            ['playId', ''],
            ['username', 123],
            ['username', ''],
            ['currency', 123],
            ['currency', ''],
            ['currency', 'KRW'],
            ['gameId', 123],
            ['gameId', ''],

        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
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
            'gameId' => 'testGameID'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(Gs5Service::class);
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
            'gameId' => 'testGameID'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockResponse = $this->createMock(Gs5Response::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testLaunchUrl.com');

        $stubService = $this->createMock(Gs5Service::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testLaunchUrl.com');

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
            'gameId' => 'testGameID'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubResponse = $this->createMock(Gs5Response::class);
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
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer invalidBearerToken');

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public function test_visual_mockService_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockCasinoService = $this->createMock(Gs5Service::class);
        $mockCasinoService->expects($this->once())
            ->method('getBetDetailUrl')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockCasinoService);
        $controller->visual(request: $request);
    }

    public function test_visual_mockResponse_casinoSuccess()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(Gs5Service::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('visualUrl.com');

        $mockResponse = $this->createMock(Gs5Response::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'visualUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visual(request: $request);
    }

    public function test_visual_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubResponse = $this->createMock(Gs5Response::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->visual(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('betParams')]
    public function test_bet_missingRequestParameter_invalidProviderRequestException($param)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        unset($request[$param]);

        $controller = $this->makeController();
        $controller->bet(request: $request);
    }

    #[DataProvider('betParams')]
    public function test_bet_invalidRequestParameter_invalidProviderRequestException($param, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $request[$param] = $value;

        $controller = $this->makeController();
        $controller->bet(request: $request);
    }

    public static function betParams()
    {
        return [
            ['access_token', 123],
            ['txn_id', 123],
            ['total_bet', 'test'],
            ['game_id', 123],
            ['ts', 'test']
        ];
    }

    public function test_bet_mockService_bet()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $mockProviderService = $this->createMock(Gs5Service::class);
        $mockProviderService->expects($this->once())
            ->method('bet')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->bet(request: $request);
    }

    public function test_bet_mockResponse_balance()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubProviderService = $this->createMock(Gs5Service::class);
        $stubProviderService->method('bet')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(Gs5Response::class);
        $mockResponse->expects($this->once())
            ->method('successTransaction')
            ->with(balance: 100.00);

        $controller = $this->makeController(service: $stubProviderService, response: $mockResponse);
        $controller->bet(request: $request);
    }

    public function test_bet_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '12345',
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubResponse = $this->createMock(Gs5Response::class);
        $stubResponse->method('successTransaction')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->bet(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('resultParams')]
    public function test_result_missingRequest_InvalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '123456',
            'total_win' => 300.00,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->result(request: $request);
    }

    #[DataProvider('resultParams')]
    public function test_result_invalidRequestType_InvalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '123456',
            'total_win' => 300.00,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->result(request: $request);
    }

    public static function resultParams()
    {
        return [
            ['access_token', 123],
            ['txn_id', 123],
            ['total_win', 'test'],
            ['game_id', 123],
            ['ts', 'test']
        ];
    }

    public function test_result_mockService_settle()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '123456',
            'total_win' => 300.00,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $mockService = $this->createMock(Gs5Service::class);
        $mockService->expects($this->once())
            ->method('settle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->result(request: $request);
    }

    public function test_result_mockResponse_successTransaction()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '123456',
            'total_win' => 300.00,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubService = $this->createMock(Gs5Service::class);
        $stubService->method('settle')
            ->willReturn(10000.00);

        $mockResponse = $this->createMock(Gs5Response::class);
        $mockResponse->expects($this->once())
            ->method('successTransaction')
            ->with(balance: 10000.00);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->result(request: $request);
    }

    public function test_result_stubResponse_expectedData()
    {
        $expectedData = new JsonResponse;

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => '123456',
            'total_win' => 300.00,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $mockResponse = $this->createMock(Gs5Response::class);
        $mockResponse->method('successTransaction')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $mockResponse);
        $response = $controller->result(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }
}