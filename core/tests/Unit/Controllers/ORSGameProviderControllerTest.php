<?php

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Player;
use App\Models\OrsPlayGame;
use Illuminate\Http\Request;
use App\Responses\OGResponse;
use Illuminate\Http\JsonResponse;
use App\Validations\RequestValidator;
use App\Services\Ors\OrsCredentialSetter;
use App\Services\Ors\OrsGameProviderService;
use App\Http\Controllers\SLOT\ORS\ORSGameProviderController;

class ORSGameProviderControllerTest extends TestCase
{
    public function makeController($validator = null, $service = null, $response = null, $credentialSetter = null)
    {
        $validator ??= $this->createStub(RequestValidator::class);
        $service ??= $this->createStub(OrsGameProviderService::class);
        $response ??= $this->createStub(OGResponse::class);
        $credentialSetter ??= $this->createStub(OrsCredentialSetter::class);

        return new ORSGameProviderController($validator, $service, $response, $credentialSetter);
    }

    public function test_authenticate_mockValidator_validate()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'player_id' => 'required|string',
                'token' => 'required|string',
                'signature' => 'required|string'
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->authenticate($request);
    }

    public function test_authenticate_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockCredentialSetter = $this->createMock(OrsCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayerID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->authenticate($request);
    }

    public function test_authenticate_mockService_authenticateToken()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockPlayerService = $this->createMock(OrsGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('authenticateToken')
            ->with($request);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->authenticate($request);
    }

    public function test_authenticateToken_mockResponse_success()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockResponse = $this->createMock(OGResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'player_status' => 'activate',
                'token' => $request->token
            ]);

        $controller = $this->makeController(null, null, $mockResponse);
        $controller->authenticate($request);
    }

    public function test_authenticateToken_stubResponse_expected()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $expected = new JsonResponse;

        $stubResponse = $this->createMock(OGResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->authenticate($request);

        $this->assertSame($expected, $response);
    }

    public function test_balance_mockValidator_validate()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'player_id' => 'required|string',
                'signature' => 'required|string'
            ]);

        $stubPlayerService = $this->createStub(OrsGameProviderService::class);
        $stubPlayerService->method('getBalance')
            ->willReturn((object)[
                'play_id' => 'test_player_id',
                'balance' => 100.0,
                'currency' => 'IDR'
            ]);

        $controller = $this->makeController($mockValidator, $stubPlayerService);
        $controller->balance($request);
    }

    public function test_balance_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockCredentialSetter = $this->createMock(OrsCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayerID');

        $stubPlayerService = $this->createStub(OrsGameProviderService::class);
        $stubPlayerService->method('getBalance')
            ->willReturn((object)[
                'play_id' => 'test_player_id',
                'balance' => 100.0,
                'currency' => 'IDR'
            ]);

        $controller = $this->makeController(null, $stubPlayerService, null, $mockCredentialSetter);
        $controller->balance($request);
    }

    public function test_balance_mockService_getBalance()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockPlayerService = $this->createMock(OrsGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('getBalance')
            ->with($request)
            ->willReturn((object)[
                'play_id' => 'test_player_id',
                'balance' => 100.0,
                'currency' => 'IDR'
            ]);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->balance($request);
    }

    public function test_balance_mockResponse_success()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockResponse = $this->createMock(OGResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'player_id' => 'test_player_id',
                'player_status' => 'activate',
                'balance' => 100.0,
                'timestamp' => Carbon::now()->timestamp,
                'currency' => 'IDR'
            ]);

        $stubPlayerService = $this->createStub(OrsGameProviderService::class);
        $stubPlayerService->method('getBalance')
            ->willReturn((object)[
                'play_id' => 'test_player_id',
                'balance' => 100.0,
                'currency' => 'IDR'
            ]);

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->balance($request);
    }

    public function test_debit_mockValidator_validate()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'player_id' => 'required|string',
                'total_amount' => 'required_if:transaction_type,debit|regex:/^\d+(\.\d{1,2})?$/',
                'transaction_type' => 'required|string|in:debit,rollback',
                'game_id' => 'required_if:transaction_type,debit|integer',
                'round_id' => 'required_if:transaction_type,debit|string',
                'called_at' => 'required|integer',
                'records' => 'required|array',
                'records.*.amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'records.*.transaction_id' => 'required|string',
                'signature' => 'required|string'
            ]);

        $stubPlayerService = $this->createStub(OrsGameProviderService::class);
        $stubPlayerService->method('bet')
            ->willReturn(100.00);

        $controller = $this->makeController($mockValidator);
        $controller->debit($request);
    }

    public function test_debit_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockCredentialSetter = $this->createMock(OrsCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayerID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->debit($request);
    }

    public function test_debit_mockService_bet()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockPlayerService = $this->createMock(OrsGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('bet')
            ->with($request)
            ->willReturn(100.00);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->debit($request);
    }

    public function test_debit_mockResponse_success()
    {
        $request = new Request([
            'player_id' => 'test_player_id',
            'total_amount' => 50.00,
            'updated_balance' => 100.00,
            'called_at' => Carbon::now()->timestamp,
            'records' => [
                'amount' => 50,
                'transaction_id' => 1,
            ],
        ]);

        $stubPlayerService = $this->createStub(OrsGameProviderService::class);
        $stubPlayerService->method('bet')
            ->willReturn(100.00);

        $mockResponse = $this->createMock(OGResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'player_id' => 'test_player_id',
                'total_amount' => 50.00,
                'updated_balance' => 100.00,
                'billing_at' => Carbon::now()->timestamp,
                'records' => [
                    'amount' => 50,
                    'transaction_id' => 1,
                ],
            ]);

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->debit($request);
    }

    public function test_debit_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $stubResponse = $this->createStub(OGResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->debit($request);

        $this->assertSame($expected, $response);
    }

    public function test_credit_mockValidator_validate()
    {
        $request = new Request([
            'player_id' => 'test_player_id',
            'amount' => 25000,
            'transaction_id' => 'sample_transactionID',
            'called_at' => Carbon::now()->timestamp
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'player_id' => 'required|string',
                'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'transaction_id' => 'required|string',
                'transaction_type' => 'required|string|in:credit',
                'round_id' => 'required|string',
                'game_id' => 'required|integer',
                'currency' => 'required|string',
                'called_at' => 'required|integer',
                'signature' => 'required|string'
            ]);

        $stubPlayerService = $this->createStub(OrsGameProviderService::class);
        $stubPlayerService->method('credit')
            ->willReturn(100.0);

        $controller = $this->makeController($mockValidator, $stubPlayerService);
        $controller->credit($request);
    }

    public function test_credit_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockCredentialSetter = $this->createMock(OrsCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayerID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->credit($request);
    }

    public function test_credit_mockService_credit()
    {
        $request = new Request([
            'player_id' => 'test_player_id',
            'amount' => 25000,
            'transaction_id' => 'sample_transactionID',
            'called_at' => Carbon::now()->timestamp
        ]);

        $mockPlayerService = $this->createMock(OrsGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('credit')
            ->with($request)
            ->willReturn(100.0);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->credit($request);
    }

    public function test_credit_mockResponse_success()
    {
        $request = new Request([
            'player_id' => 'test_player_id',
            'amount' => 25000,
            'transaction_id' => 'sample_transactionID',
            'called_at' => Carbon::now()->timestamp
        ]);

        $mockResponse = $this->createMock(OGResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'player_id' => $request->player_id,
                'amount' => $request->amount,
                'transaction_id' => $request->transaction_id,
                'updated_balance' => 100.0,
                'billing_at' => $request->called_at
            ]);

        $stubPlayerService = $this->createStub(OrsGameProviderService::class);
        $stubPlayerService->method('credit')
            ->willReturn(100.0);

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->credit($request);
    }

    public function test_credit_mockResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'player_id' => 'test_player_id',
            'amount' => 25000,
            'transaction_id' => 'sample_transactionID',
            'updated_balance' => 77000,
            'billing_at' => 4000
        ]);

        $stubResponse = $this->createStub(OGResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->credit($request);

        $this->assertEquals($expected, $response);
    }

    public function test_reward_mockValidator_validate()
    {
        $request = new Request([
            'player_id'   => 'test_play_id',
            'amount'      => 2000.00,
            'transaction_id' => 'test_trx_id',
            'called_at' => Carbon::now()->timestamp,
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'player_id' => 'required|string',
                'amount' => 'required|regex:/^\d+(\.\d{1,2})?$/',
                'transaction_id' => 'required|string',
                'game_code' => 'required|integer',
                'called_at' => 'required|integer',
                'signature' => 'required|string'
            ]);

        $stubPlayerService = $this->createStub(OrsGameProviderService::class);
        $stubPlayerService->method('promotion')
            ->willReturn(100.0);

        $controller = $this->makeController($mockValidator, $stubPlayerService);
        $controller->reward($request);
    }

    public function test_reward_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'player_id' => 'testPlayerID'
        ]);

        $mockCredentialSetter = $this->createMock(OrsCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayerID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->reward($request);
    }

    public function test_reward_mockService_promotion()
    {
        $request = new Request([
            'player_id'   => 'test_play_id',
            'amount'      => 2000.00,
            'transaction_id' => 'test_trx_id',
            'called_at' => Carbon::now()->timestamp,
        ]);

        $mockPlayerService = $this->createMock(OrsGameProviderService::class);
        $mockPlayerService->expects($this->once())
            ->method('promotion')
            ->with($request)
            ->willReturn(100.0);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->reward($request);
    }

    public function test_reward_mockResponse_success()
    {
        $request = new Request([
            'player_id'   => 'test_play_id',
            'amount'      => 2000.00,
            'transaction_id' => 'test_trx_id',
            'called_at' => Carbon::now()->timestamp,
        ]);

        $mockResponse = $this->createMock(OGResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with([
                'player_id' => $request->player_id,
                'amount' => $request->amount,
                'transaction_id' => $request->transaction_id,
                'updated_balance' => 100.0,
                'billing_at' => $request->called_at
            ]);

        $stubPlayerService = $this->createStub(OrsGameProviderService::class);
        $stubPlayerService->method('promotion')
            ->willReturn(100.0);

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->reward($request);
    }

    public function test_reward_mockResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'player_id'   => 'test_play_id',
            'amount'      => 2000.00,
            'transaction_id' => 'test_trx_id',
            'called_at' => Carbon::now()->timestamp,
        ]);

        $stubResponse = $this->createStub(OGResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->reward($request);

        $this->assertEquals($expected, $response);
    }
}
