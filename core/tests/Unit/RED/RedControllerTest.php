<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\GameProviders\V2\Red\RedService;
use App\GameProviders\V2\Red\RedResponse;
use App\GameProviders\V2\Red\RedController;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use App\GameProviders\V2\Red\Exceptions\InvalidProviderRequestException;

class RedControllerTest extends TestCase
{
    private function makeController(
        $service = null,
        $response = null
    ): RedController {
        $service ??= $this->createStub(RedService::class);
        $response ??= $this->createStub(RedResponse::class);

        return new RedController(
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
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID'
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
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public static function playParams()
    {
        return [
            ['playId', 123],
            ['memberId', 'test'],
            ['username', 123],
            ['host', 123],
            ['currency', 123],
            ['device', 'test'],
            ['gameId', 123]
        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
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
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(RedService::class);
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
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(RedService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testUrl.com');

        $mockResponse = $this->createMock(RedResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testUrl.com');

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse
        );
        $controller->play(request: $request);
    }

    public function test_play_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubResponse = $this->createMock(RedResponse::class);
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

    public function test_visual_mockService_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(RedService::class);
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
            'currency' => 'IDR'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(RedService::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('testVisualUrl.com');

        $mockResponse = $this->createMock(RedResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testVisualUrl.com');

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse
        );
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

        $stubResponse = $this->createMock(RedResponse::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->visual(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('balanceParameters')]
    public function test_balance_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->balance(request: $request);
    }

    #[DataProvider('balanceParameters')]
    public function test_balance_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->balance(request: $request);
    }

    public static function balanceParameters()
    {
        return [
            ['user_id', 'test'],
            ['prd_id', 'test'],
            ['sid', 123]
        ];
    }

    public function test_balance_mockService_getBalance()
    {
        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $mockService = $this->createMock(RedService::class);
        $mockService->expects($this->once())
            ->method('getBalance')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->balance(request: $request);
    }

    public function test_balance_mockResponse_providerSuccess()
    {
        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $stubService = $this->createMock(RedService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(RedResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse
        );
        $controller->balance(request: $request);
    }

    public function test_balance_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $stubResponse = $this->createMock(RedResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->balance(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('wagerParameters')]
    public function test_wager_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->wager(request: $request);
    }

    #[DataProvider('wagerParameters')]
    public function test_wager_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->wager(request: $request);
    }

    public static function wagerParameters()
    {
        return [
            ['user_id', 'test'],
            ['amount', 'test'],
            ['txn_id', 123],
            ['game_id', 'test'],
            ['debit_time', 123]
        ];
    }

    public function test_wager_mockService_bet()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $mockService = $this->createMock(RedService::class);
        $mockService->expects($this->once())
            ->method('bet')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->wager(request: $request);
    }

    public function test_wager_mockResponse_providerSuccess()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubService = $this->createMock(RedService::class);
        $stubService->method('bet')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(RedResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse
        );
        $controller->wager(request: $request);
    }

    public function test_wager_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubResponse = $this->createMock(RedResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->wager(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('payoutParameters')]
    public function test_payout_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->payout(request: $request);
    }

    #[DataProvider('payoutParameters')]
    public function test_payout_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->payout(request: $request);
    }

    public static function payoutParameters()
    {
        return [
            ['user_id', 'test'],
            ['amount', 'test'],
            ['txn_id', 123],
            ['game_id', 'test'],
            ['credit_time', 123]
        ];
    }

    public function test_payout_mockService_settle()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $mockService = $this->createMock(RedService::class);
        $mockService->expects($this->once())
            ->method('settle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->payout(request: $request);
    }

    public function test_payout_mockResponse_providerSuccess()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubService = $this->createMock(RedService::class);
        $stubService->method('settle')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(RedResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse
        );
        $controller->payout(request: $request);
    }

    public function test_payout_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubResponse = $this->createMock(RedResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->payout(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('bonusParameters')]
    public function test_bonus_missingRequestParameter_invalidProviderRequestException($parameter)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->bonus(request: $request);
    }

    #[DataProvider('bonusParameters')]
    public function test_bonus_invalidRequestType_invalidProviderRequestException($parameter, $data)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->bonus(request: $request);
    }

    public static function bonusParameters()
    {
        return [
            ['user_id', 'test'],
            ['amount', 'test'],
            ['txn_id', 123],
            ['game_id', 'test']
        ];
    }

    public function test_bonus_mockService_bonus()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $mockService = $this->createMock(RedService::class);
        $mockService->expects($this->once())
            ->method('bonus')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->bonus(request: $request);
    }

    public function test_bonus_mockResponse_providerSuccess()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $stubService = $this->createMock(RedService::class);
        $stubService->method('bonus')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(RedResponse::class);
        $mockResponse->expects($this->once())
            ->method('providerSuccess')
            ->with(balance: 1000.00);

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse
        );
        $controller->bonus(request: $request);
    }

    public function test_bonus_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $stubResponse = $this->createMock(RedResponse::class);
        $stubResponse->method('providerSuccess')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->bonus(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }
}