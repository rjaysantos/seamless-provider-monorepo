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

    #[DataProvider('deductParams')]
    public function test_deduct_invalidRequestParams_ProviderInvalidRequestException($param, $value)
    {
        $this->expectException(InvalidRequestException::class);

        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $request[$param] = $value;

        $controller = $this->makeController();
        $controller->deduct(request: $request);
    }

    #[DataProvider('deductParams')]
    public function test_deduct_missingRequestParams_ProviderInvalidRequestException($param)
    {
        $this->expectException(InvalidRequestException::class);

        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        unset($request[$param]);

        $controller = $this->makeController();
        $controller->deduct(request: $request);
    }

    public static function deductParams()
    {
        return [
            ['Amount', 'test'],
            ['TransferCode', 123],
            ['BetTime', 123],
            ['CompanyKey', 123],
            ['Username', 123],
            ['GameId', 'test'],
            ['ProductType', 'test']
        ];
    }

    public function test_deduct_mockService_deduct()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $mockService = $this->createMock(SboService::class);
        $mockService->expects($this->once())
            ->method('deduct')
            ->with($request);

        $controller = $this->makeController(service: $mockService);
        $controller->deduct(request: $request);
    }

    public function test_deduct_mockResponse_deduct()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $mockResponse = $this->createMock(SboResponse::class);
        $mockResponse->expects($this->once())
            ->method('deduct')
            ->with(
                $request,
                    0.0
            );

        $controller = $this->makeController(response: $mockResponse);
        $controller->deduct(request: $request);
    }

    public function test_deduct_stubResponse_expected()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $expected = new JsonResponse;
        
        $stubResponse = $this->createMock(SboResponse::class);
        $stubResponse->method('deduct')
            ->willReturn($expected);
        
        $controller = $this->makeController(response: $stubResponse);
        $result = $controller->deduct(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }
}