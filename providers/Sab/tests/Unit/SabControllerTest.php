<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Sab\SabService;
use Providers\Sab\SabResponse;
use Providers\Sab\SabController;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Sab\Exceptions\InvalidProviderRequestException;

class SabControllerTest extends TestCase
{
    private function makeController($service = null, $response = null): SabController
    {
        $service ??= $this->createStub(SabService::class);
        $response ??= $this->createStub(SabResponse::class);

        return new SabController(
            service: $service,
            response: $response
        );
    }

    #[DataProvider('playParams')]
    public function test_play_missingRequestParameter_invalidCasinoRequestException($key)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ]);

        unset($request[$key]);

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestParameter_invalidCasinoRequestException($key, $param)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ]);

        $request[$key] = $param;

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
            ['device', 'invalid-device'],
        ];
    }

    public function test_play_mockService_getLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(SabService::class);
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
            'language' => 'en',
            'device' => 1
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoResponse')
            ->with(gameUrl: 'testLaunchUrl.com');

        $stubService = $this->createMock(SabService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testLaunchUrl.com');

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
            'language' => 'en',
            'device' => 1
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('casinoResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->play(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('visualParams')]
    public function test_visual_missingRequestParameter_invalidCasinoRequestException($param)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID'
        ]);

        unset($request[$param]);

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequestParameter_invalidCasinoRequestException($param)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID'
        ]);

        $request[$param] = 123;

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public static function visualParams()
    {
        return [
            ['play_id'],
            ['bet_id']
        ];
    }

    public function test_visual_mockService_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('getBetDetailUrl')
            ->with(request: $request)
            ->willReturn('testBetDetailUrl.com');

        $controller = $this->makeController(service: $mockService);
        $controller->visual(request: $request);
    }

    public function test_visual_mockResponse_casinoResponse()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(SabService::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('testBetDetailUrl.com');

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoResponse')
            ->with(data: 'testBetDetailUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visual(request: $request);
    }

    public function test_visual_stubResponse_expectedData()
    {
        $expectedData = new JsonResponse();

        $expected = new JsonResponse($expectedData);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('casinoResponse')
            ->willReturn(new JsonResponse($expectedData));

        $stubService = $this->createMock(SabService::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('https://testBetDetailUrl.com');

        $controller = $this->makeController(response: $stubResponse, service: $stubService);
        $result = $controller->visual(request: $request);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_visualHtml_mockService_getBetDetailData()
    {
        $encryptedTrxID = 'testEncryptedTrxID';

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('getBetDetailData')
            ->with(encryptedTrxID: $encryptedTrxID)
            ->willReturn([]);

        $controller = $this->makeController(service: $mockService);
        $controller->visualHtml(encryptedTrxID: $encryptedTrxID);
    }

    public function test_visualHtml_mockResponse_visualHtml()
    {
        $stubService = $this->createMock(SabService::class);
        $stubService->method('getBetDetailData')
            ->willReturn([]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('visualHtml')
            ->with(encryptedTrxID: []);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visualHtml(encryptedTrxID: 'testEncryptedTrxID');
    }

    public function test_visualHtml_stubResponse_expectedData()
    {
        $expectedData = 'expectedData';

        $stubService = $this->createMock(SabService::class);
        $stubService->method('getBetDetailData')
            ->willReturn([]);

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('visualHtml')
            ->willReturn($expectedData);

        $controller = $this->makeController(service: $stubService, response: $stubResponse);
        $result = $controller->visualHtml(encryptedTrxID: 'testEncryptedTrxID');

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    #[DataProvider('balanceParams')]
    public function test_balance_missingRequestParameter_invalidCasinoRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        else
            unset($request['message'][$key]);

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->balance(request: $request);
    }

    #[DataProvider('balanceParams')]
    public function test_balance_invalidRequestParameter_invalidCasinoRequestException($key, $param)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ];

        if ($key === 'key' || $key === 'message')
            $request[$key] = $param;
        else
            $request['message'][$key] = $param;

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->balance(request: $request);
    }

    public static function balanceParams()
    {
        return [
            ['key', 123],
            ['message', 'test'],
            ['userId', 123]
        ];
    }

    public function test_balance_mockService_getBalance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('getBalance')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->balance(request: $request);
    }

    public function test_balance_mockResponse_balance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('balance')
            ->with(
                userID: $request->message['userId'],
                balance: 1000.00
            );

        $stubService = $this->createMock(SabService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.00);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->balance(request: $request);
    }

    public function test_balance_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('balance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->balance(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('placeBetParams')]
    public function test_placeBet_missingRequestParameter_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        else
            unset($request['message'][$key]);

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->placeBet(request: $request);
    }

    #[DataProvider('placeBetParams')]
    public function test_placeBet_invalidRequestParameter_invalidProviderRequestException($key, $param)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ];

        if ($key === 'key' || $key === 'message')
            $request[$key] = $param;
        else
            $request['message'][$key] = $param;

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->placeBet(request: $request);
    }

    public static function placeBetParams()
    {
        return [
            ['key', 123],
            ['message', 'test'],
            ['operationId', 123],
            ['refId', 123],
            ['userId', 123],
            ['betTime', 123],
            ['actualAmount', 'test'],
            ['betType', 'test'],
            ['IP', 123],
        ];
    }

    public function test_placeBet_mockService_placeBet()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('placeBet')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->placeBet(request: $request);
    }

    public function test_placeBet_mockResponse_placeBet()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('placeBet')
            ->with(refID: $request->message['refId']);

        $stubService = $this->createMock(SabService::class);
        $stubService->method('placeBet');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->placeBet(request: $request);
    }

    public function test_placeBet_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('placeBet')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->placeBet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('confirmBetParams')]
    public function test_confirmBet_missingRequestParameter_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        elseif (isset($request['message'][$key]) === true)
            unset($request['message'][$key]);
        else
            unset($request['message']['txns'][0][$key]);

        $requestData = new Request($request);

        $controller = $this->makeController();
        $controller->confirmBet(request: $requestData);
    }

    #[DataProvider('confirmBetParams')]
    public function test_confirmBet_invalidRequestParameter_invalidProviderRequestException($key, $param)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            $request[$key] = $param;
        elseif (isset($request['message'][$key]) === true)
            $request['message'][$key] = $param;
        else
            $request['message']['txns'][0][$key] = $param;

        $requestData = new Request($request);

        $controller = $this->makeController();
        $controller->confirmBet(request: $requestData);
    }

    public static function confirmBetParams()
    {
        return [
            ['key', 123],
            ['message', 'test'],
            ['operationId', 123],
            ['userId', 123],
            ['updateTime', 123],
            ['txns', 'test'],
            ['refId', 123],
            ['txId', 'test'],
            ['actualAmount', 'test']
        ];
    }

    public function test_confirmBet_mockService_confirmBet()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('confirmBet')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->confirmBet(request: $request);
    }

    public function test_confirmBet_mockResponse_successWithBalance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('successWithBalance')
            ->with(balance: 1000.0);

        $stubService = $this->createMock(SabService::class);
        $stubService->method('confirmBet')
            ->willReturn(1000.0);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->confirmBet(request: $request);
    }

    public function test_confirmBet_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('successWithBalance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->confirmBet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('cancelBetParams')]
    public function test_cancelBet_missingRequestParameter_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-cancelbet',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        elseif ($key === 'refId')
            unset($request['message']['txns'][0][$key]);
        else
            unset($request['message'][$key]);

        $requestData = new Request($request);

        $controller = $this->makeController();
        $controller->cancelBet(request: $requestData);
    }

    #[DataProvider('cancelBetParams')]
    public function test_cancelBet_invalidRequestParameter_invalidProviderRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-cancelbet',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            $request[$key] = $value;
        elseif ($key === 'refId')
            $request['message']['txns'][0][$key] = $value;
        else
            $request['message'][$key] = $value;

        $requestData = new Request($request);

        $controller = $this->makeController();
        $controller->cancelBet(request: $requestData);
    }

    public static function cancelBetParams()
    {
        return [
            ['key', 123],
            ['message', 'test'],
            ['operationId', 123],
            ['userId', 123],
            ['updateTime', 123],
            ['txns', 'test'],
            ['refId', 123]
        ];
    }

    public function test_cancelBet_mockService_cancelBet()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-cancelbet',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('cancelBet')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->cancelBet(request: $request);
    }

    public function test_cancelBet_mockResponse_successWithBalance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-cancelbet',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('successWithBalance')
            ->with(balance: 1.0);

        $stubService = $this->createMock(SabService::class);
        $stubService->method('cancelBet')
            ->willReturn(1.0);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->cancelBet(request: $request);
    }

    public function test_cancelBet_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-cancelbet',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ]);

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('successWithBalance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->cancelBet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('outstandingParams')]
    public function test_outstanding_invalidRequest_invalidCasinoRequestValdation($key, $value)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $requestDataArr = [
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ];

        $requestDataArr[$key] = $value;

        $controller = $this->makeController();
        $controller->outstanding(new Request($requestDataArr));
    }

    public static function outstandingParams()
    {
        return [
            ['currency', ''],
            ['currency', 12345],
            ['branchId', ''],
            ['branchId', 'test'],
            ['start', ''],
            ['start', 'test'],
            ['length', ''],
            ['length', 'test'],
        ];
    }

    public function test_outstanding_mockService_getRunningTransactions()
    {
        $request =  new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('getRunningTransactions')
            ->with(request: $request);

        $controller = $this->makeController($mockService);
        $controller->outstanding($request);
    }

    public function test_outstanding_mockResponse_outstanding()
    {
        $request =  new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $stubServiceRecords = collect([]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('outstanding')
            ->with(runningTransactions: $stubServiceRecords);

        $stubService = $this->createMock(SabService::class);
        $stubService->method('getRunningTransactions')
            ->willReturn($stubServiceRecords);

        $controller = $this->makeController($stubService, $mockResponse);
        $controller->outstanding($request);
    }

    public function test_outstanding_stubkResponse_expected()
    {
        $request =  new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $expectedResponse = new JsonResponse();
        $stubServiceRecords = collect([]);

        $stubResponse = $this->createStub(SabResponse::class);
        $stubResponse->method('outstanding')
            ->willReturn($expectedResponse);

        $stubService = $this->createStub(SabService::class);
        $stubService->method('getRunningTransactions')
            ->willReturn($stubServiceRecords);

        $controller = $this->makeController($stubService, $stubResponse);
        $expected = $controller->outstanding($request);

        $this->assertSame($expectedResponse, $expected);
    }

    #[DataProvider('placeBetParlayParams')]
    public function test_placeBetParlay_missingRequestParameter_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message') {
            unset($request[$key]);
        } elseif ($key === 'refId' || $key === 'betAmount') {
            unset($request['message']['txns'][0][$key]);
        } else {
            unset($request['message'][$key]);
        }

        $requestData = new Request($request);

        $controller = $this->makeController();
        $controller->placeBetParlay(request: $requestData);
    }

    #[DataProvider('placeBetParlayParams')]
    public function test_placeBetParlay_invalidRequestParameter_invalidProviderRequestException($key, $param)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message') {
            $request[$key] = $param;
        } elseif ($key === 'refId' || $key === 'betAmount') {
            $request['message']['txns'][0][$key] = $param;
        } else {
            $request['message'][$key] = $param;
        }

        $requestData = new Request($request);

        $controller = $this->makeController();
        $controller->placeBetParlay(request: $requestData);
    }

    public static function placeBetParlayParams()
    {
        return [
            ['key', 123],
            ['message', 'test'],
            ['operationId', 123],
            ['userId', 123],
            ['betTime', 123],
            ['totalBetAmount', 'test'],
            ['IP', 123],
            ['txns', 'test'],
            ['refId', 123],
            ['betAmount', 'test']
        ];
    }

    public function test_placeBetParlay_mockService_placeBetParlay()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('placeBetParlay')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_mockResponse_placeBetParlay()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('placeBetParlay')
            ->with(transactions: $request->message['txns']);

        $controller = $this->makeController(response: $mockResponse);
        $controller->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01 00:00:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('placeBetParlay')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->placeBetParlay(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('unsettleParams')]
    public function test_unsettle_missingRequestParameter_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-unsettle',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        elseif ($key === 'txns' || $key === 'operationId')
            unset($request['message'][$key]);
        else
            unset($request['message']['txns'][0][$key]);

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->unsettle(request: $request);
    }

    #[DataProvider('unsettleParams')]
    public function test_unsettle_invalidRequestParameter_invalidProviderRequestException($key, $param)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-unsettle',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            $request[$key] = $param;
        elseif ($key === 'txns' || $key === 'operationId')
            $request['message'][$key] = $param;
        else
            $request['message']['txns'][0][$key] = $param;

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->unsettle(request: $request);
    }

    public static function unsettleParams()
    {
        return [
            ['key', 123],
            ['message', 'test'],
            ['operationId', 123],
            ['txns', 'test'],
            ['userId', 123],
            ['txId', 'test'],
            ['updateTime', 123]
        ];
    }

    public function test_unsettle_mockService_unsettle()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-unsettle',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('unsettle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->unsettle(request: $request);
    }

    public function test_unsettle_mockResponse_successWithoutBalance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-unsettle',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('successWithoutBalance');

        $stubService = $this->createMock(SabService::class);
        $stubService->method('unsettle');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->unsettle(request: $request);
    }

    public function test_unsettle_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID-unsettle',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('successWithoutBalance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->unsettle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('resettleParams')]
    public function test_resettle_missingRequestParameter_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $payload = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        if (isset($payload[$key]) === true)
            unset($payload[$key]);
        elseif (isset($payload['message'][$key]) === true)
            unset($payload['message'][$key]);
        else
            unset($payload['message']['txns'][0][$key]);

        $request = new Request($payload);

        $controller = $this->makeController();
        $controller->resettle(request: $request);
    }

    #[DataProvider('resettleParams')]
    public function test_resettle_invalidRequestParameter_invalidProviderRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $payload = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ];

        if (isset($payload[$key]) === true)
            $payload[$key] = $value;
        elseif (isset($payload['message'][$key]) === true)
            $payload['message'][$key] = $value;
        else
            $payload['message']['txns'][0][$key] = $value;

        $request = new Request($payload);

        $controller = $this->makeController();
        $controller->resettle(request: $request);
    }

    public static function resettleParams()
    {
        return [
            ['key', 123],
            ['message', 'test'],
            ['operationId', 123],
            ['txns', 'test'],
            ['userId', 123],
            ['updateTime', 123],
            ['payout', 'test'],
            ['txId', 'test']
        ];
    }

    public function test_resettle_mockService_resettle()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('resettle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->resettle(request: $request);
    }

    public function test_resettle_mockResponse_successWithoutBalance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('successWithoutBalance');

        $stubService = $this->createMock(SabService::class);
        $stubService->method('resettle');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->resettle(request: $request);
    }

    public function test_resettle_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('successWithoutBalance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->resettle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('adjustBalanceParams')]
    public function test_adjustBalance_missingRequestParameter_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        if (isset($request[$key]) === true)
            unset($request[$key]);
        else if (isset($request['message'][$key]) === true)
            unset($request['message'][$key]);
        else
            unset($request['message']['balanceInfo'][$key]);

        $requestData = new Request($request);

        $controller = $this->makeController();
        $controller->adjustBalance(request: $requestData);
    }

    #[DataProvider('adjustBalanceParams')]
    public function test_adjustBalance_invalidRequestParameterDataType_invalidProviderRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ];

        if (isset($request[$key]) === true)
            $request[$key] = $value;
        else if (isset($request['message'][$key]) === true)
            $request['message'][$key] = $value;
        else
            $request['message']['balanceInfo'][$key] = $value;

        $requestData = new Request($request);

        $controller = $this->makeController();
        $controller->adjustBalance(request: $requestData);
    }

    public static function adjustBalanceParams()
    {
        return [
            ['key', 123],
            ['message', 'invalid'],
            ['operationId', 123],
            ['userId', 123],
            ['txId', 'invalid'],
            ['time', 123],
            ['betType', 'invalid'],
            ['balanceInfo', 'invalid'],
            ['creditAmount', 'invalid'],
            ['debitAmount', 'invalid'],
        ];
    }

    public function test_adjustBalance_mockService_adjustBalance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('adjustBalance')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->adjustBalance(request: $request);
    }

    public function test_adjustBalance_mockResponse_adjustBalance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('successWithoutBalance');

        $controller = $this->makeController(response: $mockResponse);
        $controller->adjustBalance(request: $request);
    }

    public function test_adjustBalance_stubResponse_adjustBalance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $expected = new JsonResponse;

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('successWithoutBalance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $result = $controller->adjustBalance(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }

    #[DataProvider('settleParams')]
    public function test_settle_missingRequestParameter_invalidProviderRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            unset($request[$key]);
        elseif ($key === 'operationId')
            unset($request['message'][$key]);
        elseif ($key === 'txns')
            unset($request['message']['txns']);
        else
            unset($request['message']['txns'][0][$key]);

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->settle(request: $request);
    }

    #[DataProvider('settleParams')]
    public function test_settle_invalidRequestParameter_invalidProviderRequestException($key, $param)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'key' => '96l542m8kr',
            'message' => [
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ];

        if ($key === 'key' || $key === 'message')
            $request[$key] = $param;
        elseif ($key === 'operationId')
            $request['message'][$key] = $param;
        elseif ($key === 'txns')
            $request['message']['txns'] = $param;
        else
            $request['message']['txns'][0][$key] = $param;

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->settle(request: $request);
    }

    public static function settleParams()
    {
        return [
            ['key', 123],
            ['message', 'test'],
            ['operationId', 123],
            ['txns', 'test'],
            ['userId', 123],
            ['txId', 'test'],
            ['updateTime', 123],
            ['payout', 'test'],
        ];
    }

    public function test_settle_mockService_settle()
    {
        $request = new Request([
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('settle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->settle(request: $request);
    }

    public function test_settle_mockResponse_successWithoutBalance()
    {
        $request = new Request([
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('successWithoutBalance');

        $controller = $this->makeController(response: $mockResponse);
        $controller->settle(request: $request);
    }

    public function test_settle_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'key' => '96l542m8kr',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $stubResponse = $this->createMock(SabResponse::class);
        $stubResponse->method('successWithoutBalance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->settle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}
