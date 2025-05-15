<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Responses\CasinoResponse;
use Illuminate\Http\JsonResponse;
use App\Validations\RequestValidator;
use App\Services\Sbo\SboCasinoService;
use App\Services\Sbo\SboCredentialSetter;
use App\Http\Controllers\SPORTSBOOK\SBO\SBOCasinoController;

class SBOCasinoControllerTest extends TestCase
{
    public function makeController($validator = null, $service = null, $response = null, $credentialSetter = null)
    {
        $validator ??= $this->createStub(RequestValidator::class);
        $service ??= $this->createStub(SboCasinoService::class);
        $response ??= $this->createStub(CasinoResponse::class);
        $credentialSetter ??= $this->createStub(SboCredentialSetter::class);

        return new SBOCasinoController($validator, $service, $response, $credentialSetter);
    }

    public function test_play_mockValidator_validate()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'playId' => 'required|string',
                'username' => 'required|string',
                'currency' => 'required|string|in:IDR,PHP,THB,VND,BRL,USD',
                'language' => 'required|string',
                'device' => 'required|integer',
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->play($request);
    }

    public function test_play_mockCredentialSetter_setByCurrency()
    {
        $request = new Request([
            'currency' => 'testCurrency'
        ]);

        $mockCredentialSetter = $this->createMock(SboCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByCurrency')
            ->with('testCurrency');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->play($request);
    }

    public function test_play_mockService_getGameUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0
        ]);

        $mockService = $this->createMock(SboCasinoService::class);
        $mockService->expects($this->once())
            ->method('getGameUrl')
            ->with($request);

        $controller = $this->makeController(null, $mockService);
        $controller->play($request);
    }

    public function test_play_mockResponse_success()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0
        ]);

        $stubService = $this->createStub(SboCasinoService::class);
        $stubService->method('getGameUrl')
            ->willReturn('url');

        $mockResponse = $this->createMock(CasinoResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with('url&lang=en&oddstyle=ID&oddsmode=double&device=m');

        $controller = $this->makeController(null, $stubService, $mockResponse);
        $controller->play($request);
    }

    public function test_play_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 0
        ]);

        $stubService = $this->createStub(SboCasinoService::class);
        $stubService->method('getGameUrl')
            ->willReturn('url');

        $stubResponse = $this->createStub(CasinoResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, $stubService, $stubResponse);
        $response = $controller->play($request);

        $this->assertSame($expected, $response);
    }

    public function test_visual_mockValidator_validate()
    {
        $request = new Request([
            'play_id' => 'testPlayId',
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'play_id' => 'required|string',
                'bet_id' => 'sometimes',
                'txn_id' => 'required|string',
                'currency' => 'required|string',
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->visual($request);
    }

    public function test_visual_mockCredentialSetter_setByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID'
        ]);

        $mockCredentialSetter = $this->createMock(SboCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->visual($request);
    }

    public function test_visual_mockService_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayId',
        ]);

        $mockService = $this->createMock(SboCasinoService::class);
        $mockService->expects($this->once())
            ->method('getBetDetailUrl')
            ->with($request);

        $controller = $this->makeController(null, $mockService);
        $controller->visual($request);
    }

    public function test_visual_mockResponse_success()
    {
        $request = new Request([
            'play_id' => 'testPlayId',
        ]);

        $stubService = $this->createStub(SboCasinoService::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('url');

        $mockResponse = $this->createMock(CasinoResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with('url');

        $controller = $this->makeController(null, $stubService, $mockResponse);
        $controller->visual($request);
    }

    public function test_visual_stubResponse_expected()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'play_id' => 'testPlayId',
        ]);

        $stubService = $this->createStub(SboCasinoService::class);
        $stubService->method('getBetDetailUrl')
            ->willReturn('url');

        $stubResponse = $this->createStub(CasinoResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, $stubService, $stubResponse);
        $response = $controller->visual($request);

        $this->assertSame($expected, $response);
    }
}
