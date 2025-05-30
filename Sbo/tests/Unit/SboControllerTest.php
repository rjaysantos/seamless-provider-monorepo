<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Sbo\SboService;
use Providers\Sbo\SboResponse;
use Providers\Sbo\SboController;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Sbo\Exceptions\InvalidRequestException;

class SboControllerTest extends TestCase
{
    private function makeController($service = null, $response = null)
    {
        $service ??= $this->createStub(SboService::class);
        $response ??= $this->createStub(SboResponse::class);

        return new SboController($service, $response);
    }

    #[DataProvider('cancelParams')]
    public function test_cancel_missingRequest_invalidProviderRequest($parameter)
    {
        $this->expectException(InvalidRequestException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->cancel($request);
    }

    #[DataProvider('cancelParams')]
    public function test_cancel_invalidRequestType_invalidProviderRequest($parameter, $data)
    {
        $this->expectException(InvalidRequestException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->cancel($request);
    }

    public static function cancelParams()
    {
        return [
            ['CompanyKey', 123],
            ['Username', 123],
            ['TransferCode', 123]
        ];
    }

    public function test_cancel_mockService_cancel()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $mockService = $this->createMock(SboService::class);
        $mockService->expects($this->once())
            ->method('cancel')
            ->with($request);

        $controller = $this->makeController(service: $mockService);
        $controller->cancel($request);
    }

    public function test_cancel_mockResponse_cancel()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubService = $this->createMock(SboService::class);
        $stubService->method('cancel')
            ->willReturn(1000.00);

        $mockResponse = $this->createMock(SboResponse::class);
        $mockResponse->expects($this->once())
            ->method('cancel')
            ->with($request, 1000.00);

        $controller = $this->makeController(service: $stubService, response: $mockResponse);
        $controller->cancel($request);
    }

    public function test_cancel_stubResponse_expectedData()
    {
        $expectedData = new JsonResponse;

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubResponse = $this->createMock(SboResponse::class);
        $stubResponse->method('cancel')
            ->willReturn(new JsonResponse);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->cancel($request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    #[DataProvider('rollbackParams')]
    public function test_rollback_missingRequestParameter_invalidProviderRequestException($param)
    {
        $this->expectException(InvalidRequestException::class);

        $request = [
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        unset($request[$param]);

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->rollback(request: $request);
    }

    #[DataProvider('rollbackParams')]
    public function test_rollback_invalidRequestParameter_invalidProviderRequestException($param)
    {
        $this->expectException(InvalidRequestException::class);

        $request = [
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ];

        $request[$param] = 123;

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->rollback(request: $request);
    }

    public static function rollbackParams()
    {
        return [
            ['CompanyKey'],
            ['Username'],
            ['TransferCode']
        ];
    }

    public function test_rollback_mockService_rollback()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $mockService = $this->createMock(SboService::class);
        $mockService->expects($this->once())
            ->method('rollback')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->rollback(request: $request);
    }

    public function test_rollback_mockResponse_balance()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $stubService = $this->createMock(SboService::class);
        $stubService->method('rollback')
            ->willReturn(1200.0);

        $mockResponse = $this->createMock(SboResponse::class);
        $mockResponse->expects($this->once())
            ->method('balance')
            ->with(request: $request, balance: 1200.00);

        $controller = $this->makeController(response: $mockResponse, service: $stubService);
        $controller->rollback(request: $request);
    }

    public function test_rollback_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $stubResponse = $this->createMock(SboResponse::class);
        $stubResponse->method('balance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->rollback(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}