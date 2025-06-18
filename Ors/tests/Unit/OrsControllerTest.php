<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Ors\OrsService;
use Providers\Ors\OrsResponse;
use Providers\Ors\OrsController;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;
use Providers\Ors\Exceptions\InvalidProviderRequestException;

class OrsControllerTest extends TestCase
{
    private function makeController(OrsService $service = null, OrsResponse $response = null): OrsController
    {
        $service ??= $this->createStub(OrsService::class);
        $response ??= $this->createStub(OrsResponse::class);

        return new OrsController(service: $service, response: $response);
    }

    #[DataProvider('playParams')]
    public function test_play_missingRequestParameter_invalidCasinoRequestException($unset)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestType_invalidCasinoRequestException($key)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

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
            ['language']
        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public function test_play_mockService_getLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(OrsService::class);
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
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testUrl.com');

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->play(request: $request);
    }

    public function test_play_stubResponse_expectedData()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $expected = new JsonResponse;

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testUrl.com');

        $stubResponse = $this->createMock(OrsResponse::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubService, response: $stubResponse);
        $response = $controller->play(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('visualParams')]
    public function test_visual_missingRequestParameter_invalidCasinoRequestException($unset)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    #[DataProvider('visualParams')]
    public function test_visual_invalidRequestType_invalidCasinoRequestException($key)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

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

        $controller = $this->makeController();
        $controller->visual(request: $request);
    }

    public function test_visual_mockService_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $mockService = $this->createMock(OrsService::class);
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
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('testUrl.com');

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with(data: 'testUrl.com');

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->visual(request: $request);
    }

    public function test_visual_stubResponse_expectedData()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . env('FEATURE_TEST_TOKEN'));

        $expected = new JsonResponse;

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('testUrl.com');

        $stubResponse = $this->createMock(OrsResponse::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubService, response: $stubResponse);
        $response = $controller->visual(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_missingRequestParameter_invalidProviderRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'player_id' => 'testPlayID',
            'token' => 'testToken',
            'signature' => 'testSignature'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->authenticate(request: $request);
    }

    #[DataProvider('authenticateParams')]
    public function test_authenticate_invalidRequestType_invalidCasinoRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'player_id' => 'testPlayID',
            'token' => 'testToken',
            'signature' => 'testSignature'
        ]);

        $request[$key] = 123;

        $controller = $this->makeController();
        $controller->authenticate(request: $request);
    }

    public static function authenticateParams()
    {
        return [
            ['player_id'],
            ['token'],
            ['signature']
        ];
    }

    public function test_authenticate_mockService_authenticate()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'token' => 'testToken',
            'signature' => 'testSignature'
        ]);

        $mockService = $this->createMock(OrsService::class);
        $mockService->expects($this->once())
            ->method('authenticate')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_mockResponse_authenticate()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'token' => 'testToken',
            'signature' => 'testSignature'
        ]);

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->expects($this->once())
            ->method('authenticate')
            ->with(token: $request->token);

        $controller = $this->makeController(response: $mockResponse);
        $controller->authenticate(request: $request);
    }

    public function test_authenticate_stubResponse_expectedData()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'token' => 'testToken',
            'signature' => 'testSignature'
        ]);

        $expected = new JsonResponse;

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->method('authenticate')
            ->willReturn($expected);

        $controller = $this->makeController(response: $mockResponse);
        $response = $controller->authenticate(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('getBalanceParams')]
    public function test_getBalance_missingRequestParameter_invalidProviderRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'player_id' => 'testPlayID',
            'signature' => 'testSignature'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->getBalance(request: $request);
    }

    #[DataProvider('getBalanceParams')]
    public function test_getBalance_invalidRequestType_invalidCasinoRequestException($key)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'player_id' => 'testPlayID',
            'signature' => 'testSignature'
        ]);

        $request[$key] = 123;

        $controller = $this->makeController();
        $controller->getBalance(request: $request);
    }

    public static function getBalanceParams()
    {
        return [
            ['player_id'],
            ['signature']
        ];
    }

    public function test_getBalance_mockService_getBalance()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'signature' => 'testSignature'
        ]);

        $player = (object) [
            'balance' => 100,
            'currency' => 'IDR'
        ];

        $mockService = $this->createMock(OrsService::class);
        $mockService->expects($this->once())
            ->method('getBalance')
            ->with(request: $request)
            ->willReturn($player);

        $controller = $this->makeController(service: $mockService);
        $controller->getBalance(request: $request);
    }

    public function test_getBalance_mockResponse_getBalance()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'signature' => 'testSignature'
        ]);

        $playerBalanceDetails = (object) [
            'balance' => 100,
            'currency' => 'IDR'
        ];

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('getBalance')
            ->willReturn($playerBalanceDetails);

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->expects($this->once())
            ->method('getBalance')
            ->with(
                playID: $request->player_id,
                balance: $playerBalanceDetails->balance,
                currency: $playerBalanceDetails->currency
            );

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->getBalance(request: $request);
    }

    public function test_getBalance_stubResponse_expectedData()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'signature' => 'testSignature'
        ]);

        $player = (object) [
            'balance' => 100,
            'currency' => 'IDR'
        ];

        $expected = new JsonResponse;

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('getBalance')
            ->willReturn($player);

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->method('getBalance')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $response = $controller->getBalance(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('debitParams')]
    public function test_debit_missingRequestParameter_invalidCasinoRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $record = (object) [
            'transaction_id' => 'test_transacID_1',
            'amount' => 150
        ];

        if (isset($request[$unset]) === false)
            unset($record->$unset);

        $request = new Request([
            'player_id' => 'testPlayID',
            'total_amount' => 250,
            'transaction_type' => 'debit',
            'game_id' => 123,
            'round_id' => 'testRoundID',
            'called_at' => 1234567891,
            'records' => [
                $record
            ],
            'signature' => 'testSignature'
        ]);

        if (isset($request[$unset]) === true)
            unset($request[$unset]);

        $controller = $this->makeController();
        $controller->debit(request: $request);
    }

    #[DataProvider('debitParams')]
    public function test_debit_invalidRequestType_invalidCasinoRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $record = (object) [
            'transaction_id' => 'test_transacID_1',
            'amount' => 150
        ];


        if (isset($request[$key]) === false)
            $record->$key = $value;

        $request = new Request([
            'player_id' => 'testPlayID',
            'total_amount' => 250,
            'transaction_type' => 'debit',
            'game_id' => 123,
            'round_id' => 'testRoundID',
            'called_at' => 1234567891,
            'records' => [
                $record
            ],
            'signature' => 'testSignature'
        ]);

        if (isset($request[$key]) === true)
            $request[$key] = $value;

        $controller = $this->makeController();
        $controller->debit(request: $request);
    }

    public static function debitParams()
    {
        return [
            ['player_id', 123],
            ['total_amount', 'invalidValue'],
            ['transaction_type', 123],
            ['game_id', 'invalidValue'],
            ['round_id', 123],
            ['called_at', 'invalidValue'],
            ['records', 'invalidValue'],
            ['transaction_id', 123],
            ['amount', 'invalidValue'],
            ['signature', 123],
        ];
    }

    public function test_debit_mockService_bet()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'total_amount' => 250,
            'transaction_type' => 'debit',
            'game_id' => 123,
            'round_id' => 'testRoundID',
            'called_at' => 1234567891,
            'records' => [
                (object) [
                    'transaction_id' => 'test_transacID_1',
                    'amount' => 150
                ],
            ],
            'signature' => 'testSignature'
        ]);

        $mockService = $this->createMock(OrsService::class);
        $mockService->expects($this->once())
            ->method('bet')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->debit(request: $request);
    }

    public function test_debit_mockService_rollback()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'total_amount' => 250,
            'transaction_type' => 'rollback',
            'game_id' => 123,
            'round_id' => 'testRoundID',
            'called_at' => 1234567891,
            'records' => [
                (object) [
                    'transaction_id' => 'test_transacID_1',
                    'amount' => 150
                ],
            ],
            'signature' => 'testSignature'
        ]);

        $mockService = $this->createMock(OrsService::class);
        $mockService->expects($this->once())
            ->method('rollback')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->debit(request: $request);
    }

    public function test_debit_mockResponse_debit()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'total_amount' => 250,
            'transaction_type' => 'debit',
            'game_id' => 123,
            'round_id' => 'testRoundID',
            'called_at' => 1234567891,
            'records' => [
                (object) [
                    'transaction_id' => 'test_transacID_1',
                    'amount' => 150
                ],
            ],
            'signature' => 'testSignature'
        ]);

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('bet')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->expects($this->once())
            ->method('debit')
            ->with(request: $request, balance: 100.00);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->debit(request: $request);
    }

    public function test_debit_stubResponse_expected()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'total_amount' => 250,
            'transaction_type' => 'debit',
            'game_id' => 123,
            'round_id' => 'testRoundID',
            'called_at' => 1234567891,
            'records' => [
                (object) [
                    'transaction_id' => 'test_transacID_1',
                    'amount' => 150
                ],
            ],
            'signature' => 'testSignature'
        ]);

        $expected = new JsonResponse;

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('bet')
            ->willReturn(100.00);

        $stubResponse = $this->createMock(OrsResponse::class);
        $stubResponse->method('debit')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubService, response: $stubResponse);
        $response = $controller->debit(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('creditParams')]
    public function test_credit_missingRequestParameter_invalidProviderRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            "player_id" => "testPlayerID",
            "amount" => 30,
            "transaction_id" => "testTransactionID",
            "transaction_type" => "credit",
            "round_id" => "testRoundID",
            "game_id" => 123,
            "currency" => "IDR",
            "called_at" => 123456789,
            "signature" => "testSignature"
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->credit(request: $request);
    }

    #[DataProvider('creditParams')]
    public function test_credit_invalidRequestType_invalidCasinoRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            "player_id" => "testPlayerID",
            "amount" => 30,
            "transaction_id" => "testTransactionID",
            "transaction_type" => "credit",
            "round_id" => "testRoundID",
            "game_id" => 123,
            "currency" => "IDR",
            "called_at" => 123456789,
            "signature" => "testSignature"
        ]);

        $request[$key] = $value;

        $controller = $this->makeController();
        $controller->credit(request: $request);
    }

    public static function creditParams()
    {
        return [
            ['player_id', 123],
            ['amount', 'invalidData'],
            ['transaction_id', 123],
            ['transaction_type', 123],
            ['round_id', 123],
            ['game_id', 'invalidData'],
            ['currency', 123],
            ['called_at', 'invalidData'],
            ['signature', 123]
        ];
    }

    public function test_credit_mockService_settle()
    {
        $request = new Request([
            "player_id" => "testPlayerID",
            "amount" => 30,
            "transaction_id" => "testTransactionID",
            "transaction_type" => "credit",
            "round_id" => "testRoundID",
            "game_id" => 123,
            "currency" => "IDR",
            "called_at" => 123456789,
            "signature" => "testSignature"
        ]);

        $mockService = $this->createMock(OrsService::class);
        $mockService->expects($this->once())
            ->method('settle')
            ->with(request: $request)
            ->willReturn(100.00);

        $controller = $this->makeController(service: $mockService);
        $controller->credit(request: $request);
    }

    public function test_credit_mockResponse_credit()
    {
        $request = new Request([
            "player_id" => "testPlayerID",
            "amount" => 30,
            "transaction_id" => "testTransactionID",
            "transaction_type" => "credit",
            "round_id" => "testRoundID",
            "game_id" => 123,
            "currency" => "IDR",
            "called_at" => 123456789,
            "signature" => "testSignature"
        ]);

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('settle')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->expects($this->once())
            ->method('payout')
            ->with(request: $request, balance: 100.00);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->credit(request: $request);
    }

    public function test_credit_stubResponse_expectedData()
    {
        $request = new Request([
            "player_id" => "testPlayerID",
            "amount" => 30,
            "transaction_id" => "testTransactionID",
            "transaction_type" => "credit",
            "round_id" => "testRoundID",
            "game_id" => 123,
            "currency" => "IDR",
            "called_at" => 123456789,
            "signature" => "testSignature"
        ]);

        $expected = new JsonResponse;

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('settle')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->method('payout')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $response = $controller->credit(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    #[DataProvider('rewardParams')]
    public function test_reward_missingRequestParameter_invalidProviderRequestException($unset)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'player_id' => 'testPlayID',
            'amount' => 100.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => 123456789,
            'signature' => 'testSignature'
        ]);

        unset($request[$unset]);

        $controller = $this->makeController();
        $controller->reward(request: $request);
    }

    #[DataProvider('rewardParams')]
    public function test_reward_invalidRequestType_invalidCasinoRequestException($key, $value)
    {
        $this->expectException(InvalidProviderRequestException::class);

        $request = new Request([
            'player_id' => 'testPlayID',
            'amount' => 100.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => 123456789,
            'signature' => 'testSignature'
        ]);

        $request[$key] = $value;

        $controller = $this->makeController();
        $controller->reward(request: $request);
    }

    public static function rewardParams()
    {
        return [
            ['player_id', 123],
            ['amount', 'invalidAmount'],
            ['transaction_id', 123],
            ['game_code', 'invalidGameCode'],
            ['called_at', 'invalidTimeStamp'],
            ['signature', 123]
        ];
    }

    public function test_reward_mockService_reward()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'amount' => 100.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => 123456789,
            'signature' => 'testSignature'
        ]);

        $mockService = $this->createMock(OrsService::class);
        $mockService->expects($this->once())
            ->method('bonus')
            ->with(request: $request)
            ->willReturn(100.00);

        $controller = $this->makeController(service: $mockService);
        $controller->reward(request: $request);
    }

    public function test_reward_mockResponse_reward()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'amount' => 100.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => 123456789,
            'signature' => 'testSignature'
        ]);

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('bonus')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->expects($this->once())
            ->method('payout')
            ->with(request: $request, balance: 100.00);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->reward(request: $request);
    }

    public function test_reward_stubResponse_expectedData()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'amount' => 100.00,
            'transaction_id' => 'testTransactionID',
            'game_code' => 123,
            'called_at' => 123456789,
            'signature' => 'testSignature'
        ]);

        $expected = new JsonResponse;

        $stubService = $this->createMock(OrsService::class);
        $stubService->method('bonus')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(OrsResponse::class);
        $mockResponse->method('payout')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $response = $controller->reward(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}
