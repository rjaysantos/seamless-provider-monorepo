<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Pla\PlaService;
use Providers\Pla\PlaResponse;
use Providers\Pla\PlaController;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Pla\Exceptions\InvalidProviderRequestException;

class PlaControllerTest extends TestCase
{
    private function makeController($service = null, $response = null): PlaController
    {
        $service ??= $this->createStub(PlaService::class);
        $response ??= $this->createStub(PlaResponse::class);

        return new PlaController(service: $service, response: $response);
    }

    #[DataProvider('playParams')]
    public function test_play_missingRequestParameter_invalidCasinoRequestException($unset)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        unset($request[$unset]);

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
            'gameId' => 'testGameID',
            'device' => 1
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
            ['gameId', 123],
            ['device', 'web']
        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);
        $request->headers->set('Authorization', 'Bearer ' . 'invalidBearerToken');

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public function test_play_mockService_getLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(PlaService::class);
        $mockService->expects($this->once())
            ->method('getLaunchUrl')
            ->with($request);

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
            'gameId' => 'testGameID',
            'device' => 1
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(PlaService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testUrl.com');

        $mockResponse = $this->createMock(PlaResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with('testUrl.com');

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
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(PlaService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testUrl.com');

        $stubResponse = $this->createMock(PlaResponse::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(service: $stubService, response: $stubResponse);
        $response = $controller->play(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_missingRequestParameter_invalidProviderRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->authenticate(request: $request);
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_invalidRequestParameterDataType_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $request[$key] = 34561;

        $controller = $this->makeController();
        $controller->authenticate(request: $request);
    }

    public static function authenticateParams()
    {
        return [
            ['requestId'],
            ['username'],
            ['externalToken'],
        ];
    }

    public function test_authenticate_mockService_authenticate()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $mockProviderService = $this->createMock(PlaService::class);
        $mockProviderService->expects($this->once())
            ->method('authenticate')
            ->with($request)
            ->willReturn('IDR');

        $controller = $this->makeController(service: $mockProviderService);
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_mockResponse_authenticate()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $stubProviderService = $this->createMock(PlaService::class);
        $stubProviderService->method('authenticate')
            ->willReturn('IDR');

        $mockResponse = $this->createMock(PlaResponse::class);
        $mockResponse->expects($this->once())
            ->method('authenticate')
            ->with($request->requestId, $request->username, 'IDR');

        $controller = $this->makeController(service: $stubProviderService, response: $mockResponse);
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_stubResponse_expected()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $expected = new JsonResponse();

        $stubResponse = $this->createMock(PlaResponse::class);
        $stubResponse->method('authenticate')
            ->willReturn($expected);

        $stubProviderService = $this->createMock(PlaService::class);
        $stubProviderService->method('authenticate')
            ->willReturn('IDR');

        $controller = $this->makeController(service: $stubProviderService, response: $stubResponse);
        $response = $controller->authenticate(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('getBalanceParams')]
    public function test_getBalance_missingRequestParameter_invalidProviderRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->getBalance(request: $request);
    }

    #[DataProvider('getBalanceParams')]
    public function test_getBalance_invalidRequestParameterDataType_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $request[$key] = 34561;

        $controller = $this->makeController();
        $controller->getBalance(request: $request);
    }

    public static function getBalanceParams()
    {
        return [
            ['requestId'],
            ['username'],
            ['externalToken'],
        ];
    }

    public function test_getBalance_mockService_getBalance()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $mockProviderService = $this->createMock(PlaService::class);
        $mockProviderService->expects($this->once())
            ->method('getBalance')
            ->with($request)
            ->willReturn(0.00);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->getBalance(request: $request);
    }

    public function test_getBalance_mockResponse_getBalance()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $mockResponse = $this->createMock(PlaResponse::class);
        $mockResponse->expects($this->once())
            ->method('getBalance')
            ->with($request->requestId, 0.00);

        $controller = $this->makeController(response: $mockResponse);
        $controller->getBalance(request: $request);
    }

    public function test_getBalance_stubResponse_expected()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $expected = new JsonResponse();

        $stubResponse = $this->createMock(PlaResponse::class);
        $stubResponse->method('getBalance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->getBalance(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_healthCheck_mockResponse_healthCheck()
    {
        $mockResponse = $this->createMock(PlaResponse::class);
        $mockResponse->expects($this->once())
            ->method('healthCheck');

        $controller = $this->makeController(response: $mockResponse);
        $controller->healthCheck();
    }

    public function test_healthCheck_stubResponse_expected()
    {
        $expected = new JsonResponse();

        $stubResponse = $this->createMock(PlaResponse::class);
        $stubResponse->method('healthCheck')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->healthCheck();

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('logoutParams')]
    public function test_logout_missingRequestParameter_invalidProviderRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->logout(request: $request);
    }

    #[DataProvider('logoutParams')]
    public function test_logout_invalidRequestParameterDataType_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $request[$key] = 34561;

        $controller = $this->makeController();
        $controller->logout(request: $request);
    }

    public static function logoutParams()
    {
        return [
            ['requestId'],
            ['username'],
            ['externalToken'],
        ];
    }

    public function test_logout_mockService_logout()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $mockProviderService = $this->createMock(PlaService::class);
        $mockProviderService->expects($this->once())
            ->method('logout')
            ->with($request);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->logout(request: $request);
    }

    public function test_logout_mockResponse_logout()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $mockResponse = $this->createMock(PlaResponse::class);
        $mockResponse->expects($this->once())
            ->method('logout')
            ->with($request->requestId);

        $controller = $this->makeController(response: $mockResponse);
        $controller->logout(request: $request);
    }

    public function test_logout_stubResponse_expected()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $expected = new JsonResponse();

        $stubResponse = $this->createMock(PlaResponse::class);
        $stubResponse->method('logout')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->logout(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('betParams')]
    public function test_bet_missingRequestParameter_invalidProviderRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-04-07 00:00:00',
            'amount' => '100',
            'gameCodeName' => 'testGameID'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->bet(request: $request);
    }

    #[DataProvider('betParams')]
    public function test_bet_invalidRequestParameterDataType_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-04-07 00:00:00',
            'amount' => '100',
            'gameCodeName' => 'testGameID'
        ]);

        $request[$key] = 34561;

        $controller = $this->makeController();
        $controller->bet(request: $request);
    }

    public static function betParams()
    {
        return [
            ['requestId'],
            ['username'],
            ['externalToken'],
            ['gameRoundCode'],
            ['transactionCode'],
            ['transactionDate'],
            ['amount'],
            ['gameCodeName']
        ];
    }

    public function test_bet_mockService_bet()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-04-07 00:00:00',
            'amount' => '100',
            'gameCodeName' => 'testGameID'
        ]);

        $mockProviderService = $this->createMock(PlaService::class);
        $mockProviderService->expects($this->once())
            ->method('bet')
            ->with($request)
            ->willReturn(0.00);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->bet(request: $request);
    }

    public function test_bet_mockResponse_bet()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-04-07 00:00:00',
            'amount' => '100',
            'gameCodeName' => 'testGameID'
        ]);

        $stubProviderService = $this->createMock(PlaService::class);
        $stubProviderService->method('bet')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(PlaResponse::class);
        $mockResponse->expects($this->once())
            ->method('bet')
            ->with($request, 100.00);

        $controller = $this->makeController(response: $mockResponse, service: $stubProviderService);
        $controller->bet(request: $request);
    }

    public function test_bet_stubResponse_expected()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-04-07 00:00:00',
            'amount' => '100',
            'gameCodeName' => 'testGameID'
        ]);

        $expected = new JsonResponse();

        $stubResponse = $this->createMock(PlaResponse::class);
        $stubResponse->method('bet')
            ->willReturn($expected);

        $stubProviderService = $this->createMock(PlaService::class);
        $stubProviderService->method('bet')
            ->willReturn(100.00);

        $controller = $this->makeController(response: $stubResponse, service: $stubProviderService);
        $response = $controller->bet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('gameRoundResultRefundParams')]
    public function test_gameRoundResult_refundMissingRequestParameter_invalidProviderRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $payDetails = [
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '10',
            'type' => 'REFUND',
            'relatedTransactionCode' => 'testRelatedTransactionCode'
        ];

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => $payDetails,
            'gameCodeName' => 'testCodeName'
        ]);

        if (isset($request[$unset]) === true)
            unset($request[$unset]);
        else {
            unset($payDetails[$unset]);
            $request['pay'] = $payDetails;
        }

        $controller = $this->makeController();
        $controller->gameRoundResult(request: $request);
    }

    #[DataProvider('gameRoundResultRefundParams')]
    public function test_gameRoundResult_refundInvalidRequestParameterDataType_invalidProviderRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $payDetails = [
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '10',
            'type' => 'WIN',
            'relatedTransactionCode' => 'testRelatedTransactionCode'
        ];

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => $payDetails,
            'gameCodeName' => 'testCodeName'
        ]);

        if (isset($request[$key]) === true)
            $request[$key] = $value;
        else {
            $payDetails[$key] = $value;
            $request['pay'] = $payDetails;
        }

        $controller = $this->makeController();
        $controller->gameRoundResult(request: $request);
    }

    public static function gameRoundResultRefundParams()
    {
        return [
            ['requestId', 34561],
            ['username', 34561],
            ['gameRoundCode', 34561],
            ['transactionCode', 34561],
            ['transactionDate', 34561],
            ['amount', 'test'],
            ['type', 34561],
            ['relatedTransactionCode', 34561],
            ['gameCodeName', 34561]
        ];
    }

    public function test_gameRoundResult_mockService_refund()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testCodeName'
        ]);

        $mockProviderService = $this->createMock(PlaService::class);
        $mockProviderService->expects($this->once())
            ->method('refund')
            ->with($request)
            ->willReturn(0.00);

        $controller = $this->makeController(service: $mockProviderService);
        $controller->gameRoundResult(request: $request);
    }

    public function test_gameRoundResult_mockResponse_gameRoundResult() 
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testCodeName'
        ]);

        $stubProviderService = $this->createMock(PlaService::class);
        $stubProviderService->method('settle')
            ->willReturn(0.00);

        $mockResponse = $this->createMock(PlaResponse::class);
        $mockResponse->expects($this->once())
            ->method('gameRoundResult')
            ->with($request, 0.00);

        $controller = $this->makeController(service: $stubProviderService, response: $mockResponse);
        $controller->gameRoundResult(request: $request);
    }

    public function test_gameRoundResult_stubResponse_expected() 
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testCodeName'
        ]);

        $expected = new JsonResponse();

        $stubProviderService = $this->createMock(PlaService::class);
        $stubProviderService->method('settle')
            ->willReturn(0.00);

        $stubResponse = $this->createMock(PlaResponse::class);
        $stubResponse->method('gameRoundResult')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubProviderService, response: $stubResponse);
        $response = $controller->gameRoundResult(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('visualParams')]
    public function test_visual_missingRequestParameter_invalidCasinoRequestException($unset)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequestType_invalidCasinoRequestException($parameter, $data)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public static function visualParams()
    {
        return [
            ['play_id', 123],
            ['bet_id', 123],
            ['currency', 123]
        ];
    }

    public function test_visual_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . 'invalidBearerToken');

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public function test_visual_mockService_getBetDetail()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(PlaService::class);
        $mockService->expects($this->once())
            ->method('getBetDetail')
            ->with($request);

        $controller = $this->makeController(service: $mockService);
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

        $stubService = $this->createMock(PlaService::class);
        $stubService->method('getBetDetail')
            ->willReturn('testUrl.com');

        $mockResponse = $this->createMock(PlaResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with('testUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visual(request: $request);
    }

    public function test_visual_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(PlaService::class);
        $stubService->method('getBetDetail')
            ->willReturn('testUrl.com');

        $stubResponse = $this->createMock(PlaResponse::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(service: $stubService, response: $stubResponse);
        $response = $controller->visual(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }
}
