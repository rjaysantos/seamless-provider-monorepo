<?php

use Illuminate\Http\JsonResponse;
use Tests\TestCase;
use Illuminate\Http\Request;
use App\Validations\RequestValidator;
use App\Http\Controllers\SLOT\RED\REDCasinoController;
use App\Responses\CasinoResponse;
use App\Services\Red\RedCasinoService;
use App\Services\Red\RedCredentialSetter;

class REDCasinoControllerTest extends TestCase
{
    public function makeController($validator = null, $service = null, $response = null, $credentialSetter = null)
    {
        $validator ??= $this->createStub(RequestValidator::class);
        $service ??= $this->createStub(RedCasinoService::class);
        $response ??= $this->createStub(CasinoResponse::class);
        $credentialSetter ??= $this->createStub(RedCredentialSetter::class);

        return new REDCasinoController($validator, $service, $response, $credentialSetter);
    }

    public function test_play_mockValidator_validate()
    {
        $request = new Request([
            'currency' => 'IDR'
        ]);

        $mockValidator = $this->createMock(RequestValidator::class);
        $mockValidator->expects($this->once())
            ->method('validate')
            ->with($request, [
                'playId'    => 'required|string',
                'memberId'  => 'required|integer',
                'username'  => 'required|string',
                'language'  => 'required|string',
                'country'   => 'required|string',
                'host'      => 'required|string',
                'currency'  => 'required|string|in:IDR,PHP,THB,VND,BRL,USD',
                'device'    => 'required|integer',
                'gameId'    => 'required|string',
            ]);

        $controller = $this->makeController($mockValidator);
        $controller->play($request);
    }

    public function test_play_mockCredentialSetter_setByCurrency()
    {
        $request = new Request([
            'currency' => 'testCurrency'
        ]);

        $mockCredentialSetter = $this->createMock(RedCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByCurrency')
            ->with('testCurrency');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->play($request);
    }

    public function test_play_mockService_play()
    {
        $request = new Request([
            'currency' => 'IDR'
        ]);

        $mockPlayerService = $this->createMock(RedCasinoService::class);
        $mockPlayerService->expects($this->once())
            ->method('play')
            ->with($request);

        $controller = $this->makeController(null, $mockPlayerService);
        $controller->play($request);
    }

    public function test_play_mockResponse_success()
    {
        $request = new Request([
            'currency' => 'IDR'
        ]);

        $stubPlayerService = $this->createStub(RedCasinoService::class);
        $stubPlayerService->method('play')
            ->willReturn('launchUrl');

        $mockResponse = $this->createMock(CasinoResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with('launchUrl');

        $controller = $this->makeController(null, $stubPlayerService, $mockResponse);
        $controller->play($request);
    }

    public function test_play_stubResponse_expected()
    {
        $request = new Request([
            'currency' => 'IDR'
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

        $mockCredentialSetter = $this->createMock(RedCredentialSetter::class);
        $mockCredentialSetter->expects($this->once())
            ->method('setByPlayID')
            ->with('testPlayID');

        $controller = $this->makeController(null, null, null, $mockCredentialSetter);
        $controller->visual($request);
    }

    public function test_visual_mockService_getBetResultUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
        ]);

        $mockPlayerService = $this->createMock(RedCasinoService::class);
        $mockPlayerService->expects($this->once())
            ->method('getBetResultUrl')
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

        $betResultUrl = 'betResultUrl';

        $mockResponse = $this->createMock(CasinoResponse::class);
        $mockResponse->expects($this->once())
            ->method('success')
            ->with($betResultUrl);

        $stubPlayerService = $this->createStub(RedCasinoService::class);
        $stubPlayerService->method('getBetResultUrl')
            ->willReturn($betResultUrl);

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

        $stubResponse = $this->createStub(CasinoResponse::class);
        $stubResponse->method('success')
            ->willReturn($expected);

        $controller = $this->makeController(null, null, $stubResponse);
        $response = $controller->visual($request);

        $this->assertSame($expected, $response);
    }
}
