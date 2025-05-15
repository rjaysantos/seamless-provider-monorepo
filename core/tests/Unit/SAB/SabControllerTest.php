<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\GameProviders\V2\Sab\SabService;
use App\GameProviders\V2\Sab\SabResponse;
use App\GameProviders\V2\Sab\SabController;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use App\GameProviders\V2\Sab\Contracts\ISportsbookDetails;
use App\GameProviders\V2\Sab\Exceptions\InvalidProviderRequestException;

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

        $stubSportsbookDetails = $this->createMock(ISportsbookDetails::class);

        $mockService = $this->createMock(SabService::class);
        $mockService->expects($this->once())
            ->method('getBetDetailData')
            ->with(encryptedTrxID: $encryptedTrxID)
            ->willReturn($stubSportsbookDetails);

        $controller = $this->makeController(service: $mockService);
        $controller->visualHtml(encryptedTrxID: $encryptedTrxID);
    }

    public function test_visualHtml_mockResponse_visualHtml()
    {
        $stubSportsbookDetails = $this->createMock(ISportsbookDetails::class);

        $stubService = $this->createMock(SabService::class);
        $stubService->method('getBetDetailData')
            ->willReturn($stubSportsbookDetails);

        $mockResponse = $this->createMock(SabResponse::class);
        $mockResponse->expects($this->once())
            ->method('visualHtml')
            ->with(sportsbookDetails: $stubSportsbookDetails);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visualHtml(encryptedTrxID: 'testEncryptedTrxID');
    }

    public function test_visualHtml_stubResponse_expectedData()
    {
        $expectedData = [
            'ticketID' => 'testTransactioID',
            'dateTimeSettle' => '2025-01-01 12:00:00',
            'event' => 'TestEvent',
            'match' => 'Team A vs Team B',
            'betType' => 'TestMarket',
            'betChoice' => 'Team A',
            'hdp' => '1.5',
            'odds' => '2.0',
            'oddsType' => '1',
            'betAmount' => 100.00,
            'score' => '1',
            'status' => 'Win',
            'mixParleyData' => [],
            'singleParleyData' => []
        ];

        $stubSportsbookDetails = $this->createMock(ISportsbookDetails::class);

        $stubService = $this->createMock(SabService::class);
        $stubService->method('getBetDetailData')
            ->willReturn($stubSportsbookDetails);

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
        elseif ($key === 'userId' || $key === 'updateTime' || $key === 'txns')
            unset($request['message'][$key]);
        elseif ($key === 'refId' || $key === 'txId' || $key === 'actualAmount')
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
        elseif ($key === 'userId' || $key === 'updateTime' || $key === 'txns')
            $request['message'][$key] = $param;
        elseif ($key === 'refId' || $key === 'txId' || $key === 'actualAmount')
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

    public function test_confirmBet_mockResponse_confirmBet()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
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
            ->method('confirmBet')
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
        $stubResponse->method('confirmBet')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->confirmBet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}
