<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Responses\WhiteCliffResponse;
use App\Validations\RequestValidator;
use App\Http\Controllers\SLOT\RED\REDGameProviderController;
use App\Services\Red\RedCredentialSetter;
use App\Services\Red\RedGameProviderService;

class REDGameProviderControllerTest extends TestCase
{
    public function makeController($validator = null, $service = null, $response = null, $credentialSetter = null)
    {
        $validator ??= $this->createStub(RequestValidator::class);
        $service ??= $this->createStub(RedGameProviderService::class);
        $response ??= $this->createStub(WhiteCliffResponse::class);
        $credentialSetter ??= $this->createStub(RedCredentialSetter::class);

        return new REDGameProviderController($validator, $service, $response, $credentialSetter);
    }

    public function test_wager_mockValidator_validate()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'user_id' => 'required|integer',
                'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'txn_id' => 'required|string',
                'round_id' => 'required|string',
                'game_id' => 'required|integer',
                'credit_amount' => 'regex:/^\d+(\.\d{1,2})?$/',
                'debit_time' => 'required|date',
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->wager($request);
    }

    public function test_wager_mockRedCredentialSetter_setByUserIDProvider()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $mockRedCredentialSetter = $this->createMock(RedCredentialSetter::class);
        $mockRedCredentialSetter->expects($this->once())
            ->method('setByUserIDProvider')
            ->with(1);

        $stubPlayerService = $this->createStub(RedGameProviderService::class);
        $stubPlayerService->method('bet')
            ->willReturn((float)[
                'user_id' => 1,
                'amount' => 100.0,
                'txn_id' => 'test_txn_id',
                'round_id' => 'test_round_id',
                'game_id' => 1,
                'credit_amount' => 50.0,
                'debit_time' => '2020-01-01 00:00:00',
            ]);

        $controller = $this->makeController(null, $stubPlayerService, null, $mockRedCredentialSetter);
        $controller->wager($request);
    }

    public function test_wager_mockService_bet()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $mockService = $this->createMock(RedGameProviderService::class);
        $mockService->expects($this->once())
            ->method('bet')
            ->with($request);

        $controller = $this->makeController(null, $mockService);
        $controller->wager($request);
    }

    public function test_wager_mockResponse_success()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $stubService = $this->createStub(RedGameProviderService::class);
        $stubService->method('bet')
            ->willReturn(10.01);

        $mockResponse = $this->createMock(WhiteCliffResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'balance' => 10.01
            ])
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(null, $stubService, $mockResponse);
        $controller->wager($request);
    }

    public function test_wager_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'user_id' => 1
        ]);

        $stubResponse = $this->createStub(WhiteCliffResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->wager($request);

        $this->assertSame($expected, $response);
    }

    public function test_payout_mockValidator_validate()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'user_id' => 'required|integer',
                'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'txn_id' => 'required|string',
                'round_id' => 'required|string',
                'credit_time' => 'required|date',
                'game_id' => 'required|integer',
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->payout($request);
    }

    public function test_payout_mockRedCredentialSetter_setByUserIDProvider()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $mockRedCredentialSetter = $this->createMock(RedCredentialSetter::class);
        $mockRedCredentialSetter->expects($this->once())
            ->method('setByUserIDProvider')
            ->with(1);

        $stubPlayerService = $this->createStub(RedGameProviderService::class);
        $stubPlayerService->method('settleBet')
            ->willReturn((float)[
                'user_id' => 1,
                'amount' => 100.0,
                'txn_id' => 'test_txn_id',
                'round_id' => 'test_round_id',
                'credit_time' => '2020-01-01 00:00:00',
                'game_id' => 1,
            ]);

        $controller = $this->makeController(null, $stubPlayerService, null, $mockRedCredentialSetter);
        $controller->payout($request);
    }

    public function test_payout_mockService_settleBet()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $mockService = $this->createMock(RedGameProviderService::class);
        $mockService->expects($this->once())
            ->method('settleBet')
            ->with($request);

        $controller = $this->makeController(null, $mockService);
        $controller->payout($request);
    }

    public function test_payout_mockResponse_success()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $stubService = $this->createStub(RedGameProviderService::class);
        $stubService->method('settleBet')
            ->willReturn(10.01);

        $mockResponse = $this->createMock(WhiteCliffResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'balance' => 10.01
            ])
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(null, $stubService, $mockResponse);
        $controller->payout($request);
    }

    public function test_payout_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'user_id' => 1
        ]);

        $stubResponse = $this->createStub(WhiteCliffResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->payout($request);

        $this->assertSame($expected, $response);
    }

    public function test_bonus_mockValidator_validate()
    {
        $request = new Request([
            'user_id'   => 1,
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'user_id'   => 'required|integer',
                'amount'    => 'required|integer',
                'txn_id'    => 'required|string',
                'game_id'   => 'required|integer',
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->bonus($request);
    }

    public function test_bonus_mockRedCredentialSetter_setByUserIDProvider()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $mockRedCredentialSetter = $this->createMock(RedCredentialSetter::class);
        $mockRedCredentialSetter->expects($this->once())
            ->method('setByUserIDProvider')
            ->with(1);

        $stubPlayerService = $this->createStub(RedGameProviderService::class);
        $stubPlayerService->method('settleBet')
            ->willReturn((float)[
                'user_id' => 1,
                'amount' => 100.0,
                'txn_id' => 'test_txn_id',
                'game_id' => 1,
            ]);

        $controller = $this->makeController(null, $stubPlayerService, null, $mockRedCredentialSetter);
        $controller->bonus($request);
    }

    public function test_bonus_mockService_bonus()
    {
        $request = new Request([
            'user_id'   => 1,
        ]);

        $mockService = $this->createMock(RedGameProviderService::class);
        $mockService->expects($this->once())
            ->method('bonus')
            ->with($request);

        $controller = $this->makeController(null, $mockService);
        $controller->bonus($request);
    }

    public function test_bonus_mockResponse_success()
    {
        $request = new Request([
            'user_id'   => 1,
        ]);

        $stubService = $this->createStub(RedGameProviderService::class);
        $stubService->method('bonus')
            ->willReturn(10.01);

        $mockResponse = $this->createMock(WhiteCliffResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'balance' => 10.01
            ])
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(null, $stubService, $mockResponse);
        $controller->bonus($request);
    }

    public function test_bonus_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'user_id'   => 1,
        ]);

        $stubResponse = $this->createStub(WhiteCliffResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->bonus($request);

        $this->assertSame($expected, $response);
    }

    public function test_balance_mockValidator_validate()
    {
        $request = new Request([
            'user_id' => 1,
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'user_id' => 'required|integer',
                'prd_id' => 'required|integer',
                'sid' => 'required|string'
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->balance($request);
    }

    public function test_balance_mockRedCredentialSetter_setByUserIDProvider()
    {
        $request = new Request([
            'user_id' => 1
        ]);

        $mockRedCredentialSetter = $this->createMock(RedCredentialSetter::class);
        $mockRedCredentialSetter->expects($this->once())
            ->method('setByUserIDProvider')
            ->with(1);

        $stubPlayerService = $this->createStub(RedGameProviderService::class);
        $stubPlayerService->method('getBalance')
            ->willReturn((float)[
                'user_id' => 1,
                'prd_id' => 1,
                'sid' => 'test_sid',
            ]);

        $controller = $this->makeController(null, $stubPlayerService, null, $mockRedCredentialSetter);
        $controller->balance($request);
    }

    public function test_balance_mockService_getBalance()
    {
        $request = new Request([
            'user_id' => 1,
        ]);

        $mockService = $this->createMock(RedGameProviderService::class);
        $mockService->expects($this->once())
            ->method('getBalance')
            ->with($request);

        $controller = $this->makeController(null, $mockService);
        $controller->balance($request);
    }

    public function test_balance_mockResponse_success()
    {
        $request = new Request([
            'user_id' => 1,
        ]);

        $stubService = $this->createStub(RedGameProviderService::class);
        $stubService->method('getBalance')
            ->willReturn(1000.01);

        $mockResponse = $this->createMock(WhiteCliffResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'balance' => 1000.01
            ])
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(null, $stubService, $mockResponse);
        $controller->balance($request);
    }

    public function test_balance_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'user_id' => 1,
        ]);

        $stubResponse = $this->createStub(WhiteCliffResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->balance($request);

        $this->assertSame($expected, $response);
    }
}
