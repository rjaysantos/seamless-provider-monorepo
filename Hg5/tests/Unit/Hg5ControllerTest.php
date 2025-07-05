<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Hg5\Hg5Service;
use Providers\Hg5\Hg5Response;
use Providers\Hg5\Hg5Controller;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Hg5\Exceptions\InvalidProviderRequestException;

class Hg5ControllerTest extends TestCase
{
    private function makeController(
        $service = null,
        $response = null
    ): Hg5Controller {
        $service ??= $this->createStub(Hg5Service::class);
        $response ??= $this->createStub(Hg5Response::class);

        return new Hg5Controller(
            service: $service,
            response: $response
        );
    }

    #[DataProvider('playParams')]
    public function test_play_missingRequestParameter_invalidCasinoRequestException($param)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
        ]);

        unset($request[$param]);

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestType_invalidCasinoRequestException($param, $data)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
        ]);

        $request[$param] = $data;

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public static function playParams()
    {
        return [
            ['playId', 123],
            ['username', 123],
            ['currency', 123],
            ['gameId', 123]
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
            'gameId' => 'testGameID'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(Hg5Service::class);
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

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testLaunchUrl.com');

        $stubService = $this->createMock(Hg5Service::class);
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

        $stubResponse = $this->createMock(Hg5Response::class);
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

    public function test_visual_mockCasinoService_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockCasinoService = $this->createMock(Hg5Service::class);
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
            'txn_id' => null,
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('visualUrl.com');

        $mockResponse = $this->createMock(Hg5Response::class);
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

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->visual(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_visualHtml_mockService_getBetDetailData()
    {
        $encryptedPlayID = 'testEncryptedPlayID';
        $encryptedTrxID = 'testEncryptedTrxID';

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('getBetDetailData')
            ->with(encryptedPlayID: $encryptedPlayID, encryptedTrxID: $encryptedTrxID);

        $controller = $this->makeController(service: $mockService);
        $controller->visualHtml(playID: $encryptedPlayID, trxID: $encryptedTrxID);
    }

    public function test_visualHtml_mockResponse_visualHtml()
    {
        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('getBetDetailData')
            ->willReturn([]);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('visualHtml')
            ->with(data: []);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visualHtml(playID: 'testEncryptedPlayID', trxID: 'testEncryptedTrxID');
    }

    public function test_visualHtml_stubResponse_expectedData()
    {
        $path = __DIR__ . 'views/hg5_visual.blade.php';
        $expectedData = View::file($path);

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('visualHtml')
            ->willReturn(View::file($path));

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->visualHtml(playID: 'testEncryptedPlayID', trxID: 'testEncryptedTrxID');

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    #[DataProvider('visualFishGameParams')]
    public function test_visualFishGame_missingRequestParameter_expectedData($parameter)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'trxID' => 'testTransactionID',
            'playID' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->visualFishGame(request: $request);
    }

    #[DataProvider('visualFishGameParams')]
    public function test_visualFishGame_invalidRequestType_expectedData($parameter)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'trxID' => 'testTransactionID',
            'playID' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $request[$parameter] = 123;

        $controller = $this->makeController();
        $controller->visualFishGame(request: $request);
    }

    public static function visualFishGameParams()
    {
        return [
            ['trxID'],
            ['playID'],
            ['currency']
        ];
    }

    public function test_visualFishGame_mockService_getFishGameDetailUrl()
    {
        $request = new Request([
            'trxID' => 'testTransactionID',
            'playID' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('getFishGameDetaiLUrl')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->visualFishGame(request: $request);
    }

    public function test_visualFishGame_mockResponse_casinoSuccess()
    {
        $request = new Request([
            'trxID' => 'testTransactionID',
            'playID' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('getFishGameDetaiLUrl')
            ->willReturn('testFishGameUrl.com');

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with('testFishGameUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visualFishGame(request: $request);
    }

    public function test_visualFishGame_stubResponse_expectedData()
    {
        $expectedData = new JsonResponse;

        $request = new Request([
            'trxID' => 'testTransactionID',
            'playID' => 'testPlayID',
            'currency' => 'IDR'
        ]);

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->visualFishGame(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    #[DataProvider('balanceParams')]
    public function test_balance_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 123
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->balance(request: $request);
    }

    #[DataProvider('balanceParams')]
    public function test_balance_invalidRequestType_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->balance(request: $request);
    }

    public static function balanceParams()
    {
        return [
            ['playerId', 123],
            ['agentId', 'test']
        ];
    }

    public function test_balance_mockService_getBalance()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('getBalance')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->balance(request: $request);
    }

    public function test_balance_mockResponse_balance()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('getBalance')
            ->willReturn((object) [
                'balance' => 1000,
                'currency' => 'IDR'
            ]);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('balance')
            ->with(data: (object) [
                'balance' => 1000,
                'currency' => 'IDR'
            ]);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->balance(request: $request);
    }

    public function test_balance_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('balance')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->balance(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_missingRequestParameter_InvalidProviderREquestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'launchToken' => 'testLaunchToken',
            'agentId' => 111,
            'gameId' => 'testGameID',
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->authenticate(request: $request);
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_invalidRequestType_InvalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'launchToken' => 'testLaunchToken',
            'agentId' => 111,
            'gameId' => 'testGameID',
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->authenticate(request: $request);
    }

    public static function authenticateParams()
    {
        return [
            ['launchToken', 123],
            ['agentId', 'test'],
            ['gameId', 123]
        ];
    }

    public function test_authenticate_mockService_authenticate()
    {
        $request = new Request([
            'launchToken' => 'testLaunchToken',
            'agentId' => 111,
            'gameId' => 'testGameID',
        ]);

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('authenticate')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_mockResponse_authenticate()
    {
        $request = new Request([
            'launchToken' => 'testLaunchToken',
            'agentId' => 111,
            'gameId' => 'testGameID',
        ]);

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('authenticate')
            ->willReturn((object) [
                'playID' => 'testPlayerID',
                'currency' => 'IDR',
                'sessionID' => 'testSessionID',
                'balance' => 1000
            ]);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('authenticate')
            ->with((object) [
                'playID' => 'testPlayerID',
                'currency' => 'IDR',
                'sessionID' => 'testSessionID',
                'balance' => 1000
            ]);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_stubResponse_expectedData()
    {
        $expectedData = new JsonResponse;

        $request = new Request([
            'launchToken' => 'testLaunchToken',
            'agentId' => 111,
            'gameId' => 'testGameID',
        ]);

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('authenticate')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->authenticate(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    #[DataProvider('withdrawAndDepositParams')]
    public function test_withdrawAndDeposit_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            // 'mtCode' => 'testMtCode',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00',
            'extra' => [
                'slot' => [
                    'stage' => 'fg',
                    'mainBet' => '100',
                    'mainGameRound' => 'testGameRound1'
                ]
            ]
        ];

        unset($request[$parameter]);

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->withdrawAndDeposit(request: $request);
    }

    #[DataProvider('withdrawAndDepositParams')]
    public function test_withdrawAndDeposit_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00',
            'extra' => [
                'slot' => [
                    'stage' => 'fg',
                    'mainBet' => '100',
                    'mainGameRound' => 'testGameRound1'
                ]
            ]
        ];

        $request[$parameter] = $data;

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->withdrawAndDeposit(request: $request);
    }

    public static function withdrawAndDepositParams()
    {
        return [
            ['playerId', 123],
            ['agentId', 'test'],
            ['withdrawAmount', 'test'],
            ['depositAmount', 'test'],
            ['currency', 123],
            ['gameCode', 123],
            ['gameRound', 123]
        ];
    }

    public function test_withdrawAndDeposit_mockService_withdrawAndDeposit()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('betAndSettle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->withdrawAndDeposit(request: $request);
    }

    public function test_withdrawAndDeposit_mockResponse_wagerAndPayout()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('betAndSettle')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('singleTransactionResponse')
            ->with(
                data: 1000,
                currency: $request->currency,
                gameRound: $request->gameRound
            );

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->withdrawAndDeposit(request: $request);
    }

    public function test_withdrawAndDeposit_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('singleTransactionResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->withdrawAndDeposit(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('withdrawParams')]
    public function test_withdraw_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->withdraw(request: $request);
    }

    #[DataProvider('withdrawParams')]
    public function test_withdraw_invalidRequestTypeParameter_invalidPRoviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->withdraw(request: $request);
    }

    public static function withdrawParams()
    {
        return [
            ['playerId', 123],
            ['agentId', 'test'],
            ['amount', 'test'],
            ['currency', 123],
            ['gameCode', 123],
            ['gameRound', 123],
            ['eventTime', 123]
        ];
    }

    public function test_withdraw_mockService_bet()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('bet')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->withdraw(request: $request);
    }

    public function test_withdraw_mockResponse_withdraw()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('bet')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('singleTransactionResponse')
            ->with(
                balance: 1000.00,
                currency: $request->currency,
                gameRound: $request->gameRound
            );

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->withdraw(request: $request);
    }

    public function test_withdraw_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 100,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('singleTransactionResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->withdraw(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('depositParams')]
    public function test_deposit_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->deposit(request: $request);
    }

    #[DataProvider('depositParams')]
    public function test_deposit_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->deposit(request: $request);
    }

    public static function depositParams()
    {
        return [
            ['playerId', 123],
            ['agentId', 'test'],
            ['amount', 'test'],
            ['currency', 123],
            ['gameCode', 123],
            ['gameRound', 123],
            ['eventTime', 123]
        ];
    }

    public function test_deposit_mockService_settle()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('settle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->deposit(request: $request);
    }

    public function test_deposit_mockResponse_deposit()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('settle')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('singleTransactionResponse')
            ->with(
                balance: 1000.00,
                currency: $request->currency,
                gameRound: $request->gameRound
            );

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->deposit(request: $request);
    }

    public function test_deposit_stubResponse_expectedData()
    {
        $expectedData = new JsonResponse;

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('singleTransactionResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->deposit(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    #[DataProvider('multipleDepositParams')]
    public function test_multipleDeposit_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'datas' => [
                [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        if ($parameter == 'datas')
            unset($request[$parameter]);
        else
            unset($request['datas'][0][$parameter]);

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->multipleDeposit(request: $request);
    }

    #[DataProvider('multipleDepositParams')]
    public function test_multipleDeposit_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = [
            'datas' => [
                [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ];

        if ($parameter == 'datas')
            $request[$parameter] = $data;
        else
            $request['datas'][0][$parameter] = $data;

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->multipleDeposit(request: $request);
    }

    public static function multipleDepositParams()
    {
        return [
            ['datas', 123],
            ['playerId', 123],
            ['agentId', 'test'],
            ['amount', 'test'],
            ['currency', 123],
            ['gameCode', 123],
            ['gameRound', 123],
            ['eventTime', 123]
        ];
    }

    public function test_multipleDeposit_mockService_multipleSettle()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('multipleSettle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->multipleDeposit(request: $request);
    }

    public function test_multipleDeposit_mockResponse_multipleDeposit()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('multipleSettle')
            ->willReturn([(object) ['code' => '0']]);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('multipleTransactionResponse')
            ->with(data: [(object) ['code' => '0']]);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->multipleDeposit(request: $request);
    }

    public function test_multipleDeposit_stubResponse_expectedData()
    {
        $expectedData = new JsonResponse;

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ],
                (object) [
                    'playerId' => 'testPlayID2',
                    'agentId' => 111,
                    'amount' => 500.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('multipleTransactionResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->multipleDeposit(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    #[DataProvider('rolloutParams')]
    public function test_rollout_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->rollout(request: $request);
    }

    #[DataProvider('rolloutParams')]
    public function test_rollout_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->rollout(request: $request);
    }

    public static function rolloutParams()
    {
        return [
            ['playerId', 123],
            ['agentId', 'test'],
            ['currency', 123],
            ['amount', 'test'],
            ['gameCode', 123],
            ['gameRound', 123],
            ['eventTime', 123]
        ];
    }

    public function test_rollout_mockService_multiplayerBet()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('multiplayerBet')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->rollout(request: $request);
    }

    public function test_rollout_mockResponse_rollout()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('multiplayerBet')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('multiplayerTransactionResponse')
            ->with(
                balance: 1000.00,
                currency: 'IDR'
            );

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->rollout(request: $request);
    }

    public function test_rollout_stubResponse_expectedData()
    {
        $expectedData = new JsonResponse;

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $stubResponse = $this->createMock(Hg5Response::class);
        $stubResponse->method('multiplayerTransactionResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->rollout(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    #[DataProvider('rollinParams')]
    public function test_rollin_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->rollin(request: $request);
    }

    #[DataProvider('rollinParams')]
    public function test_rollin_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->rollin(request: $request);
    }

    public static function rollinParams()
    {
        return [
            ['playerId', 123],
            ['agentId', 'test'],
            ['amount', 'test'],
            ['currency', 123],
            ['gameCode', 123],
            ['gameRound', 123],
            ['eventTime', 123]
        ];
    }

    public function test_rollin_mockService_multiplayerSettle()
    {
        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $mockService = $this->createMock(Hg5Service::class);
        $mockService->expects($this->once())
            ->method('multiplayerSettle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->rollin(request: $request);
    }

    public function test_rollin_mockResponse_rollin()
    {
        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $stubService = $this->createMock(Hg5Service::class);
        $stubService->method('multiplayerSettle')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->expects($this->once())
            ->method('multiplayerTransactionResponse')
            ->with(
                balance: 1000.00,
                currency: $request->currency
            );

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->rollin(request: $request);
    }

    public function test_rollin_stubResponse_expectedData()
    {
        $expectedData = new JsonResponse;

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);

        $mockResponse = $this->createMock(Hg5Response::class);
        $mockResponse->method('multiplayerTransactionResponse')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $mockResponse);
        $response = $controller->rollin(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }
}
