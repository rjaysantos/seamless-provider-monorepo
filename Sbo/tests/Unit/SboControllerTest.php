<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Providers\Sbo\SboService;
use Providers\Sbo\SboResponse;
use Providers\Sbo\SboController;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Sbo\Exceptions\InvalidRequestException as ProviderInvalidRequestException;

class SboControllerTest extends TestCase
{
    private function makeController($service = null, $response = null): SboController
    {
        $service ??= $this->createStub(SboService::class);
        $response ??= $this->createStub(SboResponse::class);

        return new SboController(
            service: $service,
            response: $response
        );
    }

    #[DataProvider('rollbackParams')]
    public function test_rollback_missingRequestParameter_invalidProviderRequestException($param)
    {
        $this->expectException(ProviderInvalidRequestException::class);

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
        $this->expectException(ProviderInvalidRequestException::class);

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
            ->with(playID: $request->Username, balance: 1200.00);

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
