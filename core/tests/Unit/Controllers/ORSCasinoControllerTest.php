<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Responses\CasinoResponse;
use Illuminate\Http\JsonResponse;
use App\Validations\RequestValidator;
use App\Services\Ors\OrsCasinoService;
use App\Services\Ors\OrsCredentialSetter;
use App\Http\Controllers\SLOT\ORS\ORSCasinoController;

class ORSCasinoControllerTest extends TestCase
{
    public function makeController($validator = null, $service = null, $response = null, $credentialSetter = null)
    {
        $validator ??= $this->createStub(RequestValidator::class);
        $service ??= $this->createStub(OrsCasinoService::class);
        $response ??= $this->createStub(CasinoResponse::class);
        $credentialSetter ??= $this->createStub(OrsCredentialSetter::class);

        return new ORSCasinoController($validator, $service, $response, $credentialSetter);
    }

    public function test_play_mockValidator_validate()
    {
        $request = new Request([
            'currency' => 'testCurrency'
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'playId' => 'required|string',
                'username' => 'required|string',
                'currency' => 'required|string',
                'language' => 'required|string',
                'gameId' => 'required|string',
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->play($request);
    }

    public function test_play_mockCredentialSetter_setByCurrency()
    {
        $request = new Request([
            'currency' => 'testCurrency'
        ]);

        $mockCredentialSetter = $this->createMock(OrsCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByCurrency')
            ->with('testCurrency');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->play($request);
    }

    public function test_play_mockService_play()
    {
        $request = new Request([
            'currency' => 'testCurrency'
        ]);

        $mockPlayerService = $this->createMock(OrsCasinoService::class);
        $mockPlayerService->expects($this->once())
            ->method('getGameUrl')
            ->with($request);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->play($request);
    }

    public function test_play_mockResponse_success()
    {
        $request = new Request([
            'currency' => 'testCurrency'
        ]);

        $stubPlayerService = $this->createStub(OrsCasinoService::class);
        $stubPlayerService->method('getGameUrl')
            ->willReturn('gameUrl');

        $mockResponse = $this->createMock(CasinoResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with('gameUrl');

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->play($request);
    }

    public function test_play_stubResponse_expected()
    {
        $request = new Request([
            'currency' => 'testCurrency'
        ]);

        $expected = new JsonResponse;

        $stubResponse = $this->createMock(CasinoResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->play($request);

        $this->assertSame($expected, $response);
    }

    public function test_visual_mockValidator_validate()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'play_id' => 'required|string',
                'bet_id' => 'required|string',
                'currency' => 'required|string',
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->visual($request);
    }

    public function test_visual_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ]);

        $mockCredentialSetter = $this->createMock(OrsCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->visual($request);
    }

    public function test_visual_mockService_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ]);

        $mockPlayerService = $this->createMock(OrsCasinoService::class);
        $mockPlayerService->expects($this->once())
            ->method('getBetDetailUrl')
            ->with($request);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->visual($request);
    }

    public function test_visual_mockResponse_success()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ]);

        $stubPlayerService = $this->createStub(OrsCasinoService::class);
        $stubPlayerService->method('getBetDetailUrl')
            ->willReturn('recordsUrl');

        $mockResponse = $this->createMock(CasinoResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with('recordsUrl');

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->visual($request);
    }

    public function test_visual_stubResponse_expected()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ]);

        $expected = new JsonResponse;

        $stubResponse = $this->createMock(CasinoResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->visual($request);

        $this->assertSame($expected, $response);
    }
}
