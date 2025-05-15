<?php

use Tests\TestCase;
use App\Models\SboReport;
use Illuminate\Http\Request;
use App\Responses\SBOResponse;
use Illuminate\Http\JsonResponse;
use App\Validations\RequestValidator;
use App\Services\Sbo\SboCredentialSetter;
use App\Services\Sbo\SboGameProviderService;
use App\Http\Controllers\SPORTSBOOK\SBO\SBOGameProviderController;

class SBOGameProviderControllerTest extends TestCase
{
    public function makeController($validator = null, $service = null, $response = null, $credentialSetter = null)
    {
        $validator ??= $this->createStub(RequestValidator::class);
        $service ??= $this->createStub(SboGameProviderService::class);
        $response ??= $this->createStub(SBOResponse::class);
        $credentialSetter ??= $this->createStub(SboCredentialSetter::class);

        return new SBOGameProviderController($validator, $service, $response, $credentialSetter);
    }

    public function test_balance_mockValidator_validate()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id'
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'CompanyKey' => 'required|string',
                'Username' => 'required|string'
            ]);

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('getBalance')
            ->willReturn(100.0);

        $controller = $this->makeController($mockValidator, $stubPlayerService);
        $controller->balance($request);
    }

    public function test_balance_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id'
        ]);

        $mockCredentialSetter = $this->createMock(SboCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('test_play_id');

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('getBalance')
            ->willReturn((float)[
                'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
                'Username' => 'testPlayerID'
            ]);

        $controller = $this->makeController(null, $stubPlayerService, null, $mockCredentialSetter);
        $controller->balance($request);
    }

    public function test_balance_mockService_getBalance()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id'
        ]);

        $mockPlayerService = $this->createMock(SboGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('getBalance')
            ->with($request)
            ->willReturn(100.0);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->balance($request);
    }

    public function test_balance_mockResponse_success()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id'
        ]);

        $mockResponse = $this->createMock(SBOResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'AccountName' => $request->Username,
                'Balance' => 100.0
            ]);

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('getBalance')
            ->willReturn(100.0);

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->balance($request);
    }

    public function test_balance_mockResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id'
        ]);

        $stubResponse = $this->createStub(SBOResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->balance($request);

        $this->assertEquals($expected, $response);
    }

    public function test_settle_mockValidator_validate()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'TestPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'CompanyKey' => 'required|string',
                'Username' => 'required|string',
                'TransferCode' => 'required|string',
                'WinLoss' => 'required|regex:/^\d+(\.\d{1,6})?$/',
                'ResultTime' => 'required|date',
                'ProductType' => 'required|integer',
                'IsCashOut' => 'required|bool'
            ]);

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('settle')
            ->willReturn(100.0);

        $controller = $this->makeController($mockValidator, $stubPlayerService);
        $controller->settle($request);
    }

    public function test_settle_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'TestPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1
        ]);

        $mockCredentialSetter = $this->createMock(SboCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('TestPlayID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->settle($request);
    }

    public function test_settle_mockService_settleMiniGame()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'TestPlayID',
            'TransferCode' => 'fkg_testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 9
        ]);

        $mockPlayerService = $this->createMock(SboGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('settleMiniGame')
            ->with($request)
            ->willReturn(100.0);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->settle($request);
    }

    public function test_settle_mockService_settle()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'TestPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1
        ]);

        $mockPlayerService = $this->createMock(SboGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('settle')
            ->with($request)
            ->willReturn(100.0);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->settle($request);
    }

    public function test_settle_mockService_settleRngGame()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'TestPlayID',
            'TransferCode' => 'testRngTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 3
        ]);

        $mockPlayerService = $this->createMock(SboGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('settleRngGame')
            ->with($request)
            ->willReturn(100.0);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->settle($request);
    }

    public function test_settle_mockResponse_success()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'TestPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1
        ]);

        $mockResponse = $this->createMock(SBOResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'AccountName' => $request->Username,
                'Balance' => 100.0
            ]);

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('settle')
            ->willReturn(100.0);

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->settle($request);
    }

    public function test_settle_mockResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'TestPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1
        ]);

        $stubResponse = $this->createStub(SBOResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->settle($request);

        $this->assertEquals($expected, $response);
    }

    public function test_cancel_mockValidator_validate()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'CompanyKey' => 'required|string',
                'Username' => 'required|string',
                'TransferCode' => 'required|string',
            ]);

        $stubService = $this->createStub(SboGameProviderService::class);
        $stubService->method('cancel')
            ->willReturn(100.0);

        $controller = $this->makeController($mockValidator, $stubService);
        $controller->cancel($request);
    }

    public function test_cancel_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $mockCredentialSetter = $this->createMock(SboCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->cancel($request);
    }

    public function test_cancel_mockService_cancel()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $mockService = $this->createMock(SboGameProviderService::class);
        $mockService->expects($this->once())
            ->method('cancel')
            ->with($request);

        $controller = $this->makeController(null, $mockService);
        $controller->cancel($request);
    }

    public function test_cancel_mockResponse_success()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $stubService = $this->createStub(SboGameProviderService::class);
        $stubService->method('cancel')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(SBOResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'AccountName' => 'testPlayID',
                'Balance' => 100.0,
            ]);

        $controller = $this->makeController(null, $stubService, $mockResponse);
        $controller->cancel($request);
    }

    public function test_cancel_stubResponse_expected()
    {
        $expected = new JsonResponse();

        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $stubService = $this->createStub(SboGameProviderService::class);
        $stubService->method('cancel')
            ->willReturn(100.0);

        $stubResponse = $this->createStub(SBOResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, $stubService, $stubResponse);
        $response = $controller->cancel($request);

        $this->assertSame($expected, $response);
    }

    public function test_deduct_mockValidator_validate()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 123,
            'ProductType' => 1
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'Amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'TransferCode' => 'required|string',
                'BetTime' => 'required|string',
                'CompanyKey' => 'required|string',
                'Username' => 'required|string',
                'GameId' => 'required|integer',
                'ProductType' => 'required|integer'
            ]);

        $stubService = $this->createStub(SboGameProviderService::class);
        $stubService->method('deduct')
            ->willReturn(100.0);

        $controller = $this->makeController($mockValidator, $stubService);
        $controller->deduct($request);
    }

    public function test_deduct_mockService_deductMiniGame()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'TestPlayID',
            'TransferCode' => 'fkg_testTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 9
        ]);

        $mockPlayerService = $this->createMock(SboGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('deductMiniGame')
            ->with($request)
            ->willReturn(100.0);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->deduct($request);
    }

    public function test_deduct_mockService_deductRngGame()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'TestPlayID',
            'TransferCode' => 'testRngTransactionID',
            'WinLoss' => 200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 3
        ]);

        $mockPlayerService = $this->createMock(SboGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('deductRngGame')
            ->with($request)
            ->willReturn(100.0);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->deduct($request);
    }

    public function test_deduct_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 123,
            'ProductType' => 1
        ]);

        $mockCredentialSetter = $this->createMock(SboCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->deduct($request);
    }

    public function test_deduct_mockService_deduct()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 123,
            'ProductType' => 1
        ]);

        $mockService = $this->createMock(SboGameProviderService::class);
        $mockService->expects($this->once())
            ->method('deduct')
            ->with($request);

        $controller = $this->makeController(null, $mockService);
        $controller->deduct($request);
    }

    public function test_deduct_mockResponse_success()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 123,
            'ProductType' => 1
        ]);

        $stubService = $this->createStub(SboGameProviderService::class);
        $stubService->method('deduct')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(SBOResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'AccountName' => 'testPlayID',
                'Balance' => 100.00,
                'BetAmount' => 100.00
            ]);

        $controller = $this->makeController(null, $stubService, $mockResponse);
        $controller->deduct($request);
    }

    public function test_deduct_stubResponse_expected()
    {
        $expected = new JsonResponse();

        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'GameId' => 123,
            'ProductType' => 1
        ]);

        $stubService = $this->createStub(SboGameProviderService::class);
        $stubService->method('deduct')
            ->willReturn(100.0);

        $stubResponse = $this->createStub(SBOResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, $stubService, $stubResponse);
        $response = $controller->deduct($request);

        $this->assertSame($expected, $response);
    }

    public function test_bonus_mockValidator_validate()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'playID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'trxID',
            'GameId' => 1
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'CompanyKey' => 'required|string',
                'Username' => 'required|string',
                'Amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'BonusTime' => 'required|date',
                'TransferCode' => 'required|string',
                'GameId' => 'required|integer'
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->bonus($request);
    }

    public function test_bonus_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'playID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'trxID',
            'GameId' => 1
        ]);

        $mockCredentialSetter = $this->createMock(SboCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('playID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->bonus($request);
    }

    public function test_bonus_mockService_bonus()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'playID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'trxID',
            'GameId' => 1
        ]);

        $mockPlayerService = $this->createMock(SboGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('bonus')
            ->with($request);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->bonus($request);
    }

    public function test_bonus_mockResponse_success()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'playID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'trxID',
            'GameId' => 1
        ]);

        $mockResponse = $this->createMock(SBOResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'AccountName' => $request->Username,
                'Balance' => $request->Amount
            ]);

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('bonus')
            ->willReturn(900.0);

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->bonus($request);
    }

    public function test_bonus_stubResponse_expected()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'playID',
            'Amount' => 900.0,
            'BonusTime' => '2020-01-02 00:00:00',
            'TransferCode' => 'trxID',
            'GameId' => 1
        ]);

        $expected = new JsonResponse;

        $stubResponse = $this->createStub(SBOResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->bonus($request);

        $this->assertEquals($expected, $response);
    }

    public function test_status_mockValidator_validate()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id',
            'TransferCode' => 'test_trxID'
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'CompanyKey' => 'required|string',
                'Username' => 'required|string',
                'TransferCode' => 'required|string'
            ]);

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('getTransaction')
            ->willReturn(new SboReport);

        $controller = $this->makeController($mockValidator, $stubPlayerService);
        $controller->status($request);
    }

    public function test_status_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id',
            'TransferCode' => 'test_trxID'
        ]);

        $mockCredentialSetter = $this->createMock(SboCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('test_play_id');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->status($request);
    }

    public function test_status_mockService_getTransaction()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id',
            'TransferCode' => 'test_trxID'
        ]);

        $mockPlayerService = $this->createMock(SboGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('getTransaction')
            ->with($request)
            ->willReturn(new SboReport);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->status($request);
    }

    public function test_status_mockResponse_getStatus()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id',
            'TransferCode' => 'test_trxID'
        ]);

        $transaction = new SboReport;
        $transaction->trx_id = 'test_trxID';
        $transaction->win_amount = 200.0;
        $transaction->bet_amount = 100.0;
        $transaction->created_at = '2020-01-01 00:00:00';
        $transaction->updated_at = null;

        $mockResponse = $this->createMock(SBOResponse::class);
        $mockResponse->expects($this->once())
            ->method('getStatus')
            ->with($transaction);

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('getTransaction')
            ->willReturn($transaction);

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->status($request);
    }

    public function test_status_mockResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'test_play_id',
            'TransferCode' => 'test_trxID'
        ]);

        $stubResponse = $this->createStub(SBOResponse::class);
        $stubResponse->method('getStatus')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->status($request);

        $this->assertEquals($expected, $response);
    }

    public function test_rollback_mockValidator_validate()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'CompanyKey' => 'required|string',
                'Username' => 'required|string',
                'TransferCode' => 'required|string',
            ]);

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('rollback')
            ->willReturn(100.00);

        $controller = $this->makeController($mockValidator, $stubPlayerService);
        $controller->rollback($request);
    }

    public function test_rollback_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $mockCredentialSetter = $this->createMock(SboCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->status($request);
    }

    public function test_rollback_mockService_rollback()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $mockPlayerService = $this->createMock(SboGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('rollback')
            ->with($request);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->rollback($request);
    }

    public function test_rollback_mockResponse_success()
    {
        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $stubPlayerService = $this->createStub(SboGameProviderService::class);
        $stubPlayerService->method('rollback')
            ->willReturn(200.00);

        $mockResponse = $this->createMock(SBOResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'AccountName' => 'testPlayID',
                'Balance' => 200.00
            ]);

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->rollback($request);
    }

    public function test_rollback_stubResponse_expected()
    {
        $expected = new JsonResponse();

        $request = new Request([
            'CompanyKey' => 'F34A561C731843F5A0AD5FA589060FBB',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $stubResponse = $this->createStub(SBOResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->rollback($request);

        $this->assertEquals($expected, $response);
    }
}
