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

    #[DataProvider('settleParams')]
    public function test_settle_missingRequestParameter_invalidProviderRequestException($param)
    {
        $this->expectException(ProviderInvalidRequestException::class);

        $request = [
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        unset($request[$param]);

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->settle(request: $request);
    }

    #[DataProvider('settleParams')]
    public function test_settle_invalidRequestParameter_invalidProviderRequestException($param, $value)
    {
        $this->expectException(ProviderInvalidRequestException::class);

        $request = [
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ];

        $request[$param] = $value;

        $request = new Request($request);

        $controller = $this->makeController();
        $controller->settle(request: $request);
    }

    public static function settleParams()
    {
        return [
            ['CompanyKey', 123],
            ['Username', 123],
            ['TransferCode', 123],
            ['WinLoss', 'test'],
            ['ResultTime', 123],
            ['ProductType', 'test'],
            ['IsCashOut', 123]
        ];
    }

    public function test_settle_mockService_settle()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ]);

        $mockService = $this->createMock(SboService::class);
        $mockService->expects($this->once())
            ->method('settle')
            ->with(request: $request);

        $controller = $this->makeController(service: $mockService);
        $controller->settle(request: $request);
    }

    public function test_settle_mockResponse_balance()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ]);

        $stubService = $this->createMock(SboService::class);
        $stubService->method('settle')
            ->willReturn(1200.0);

        $mockResponse = $this->createMock(SboResponse::class);
        $mockResponse->expects($this->once())
            ->method('balance')
            ->with(playID: $request->Username, balance: 1200.00);

        $controller = $this->makeController(response: $mockResponse, service: $stubService);
        $controller->settle(request: $request);
    }

    public function test_settle_stubResponse_expectedData()
    {
        $expected = new JsonResponse;

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'ProductType' => 1,
            'IsCashOut' => false
        ]);

        $stubResponse = $this->createMock(SboResponse::class);
        $stubResponse->method('balance')
            ->willReturn($expected);

        $controller = $this->makeController(response: $stubResponse);
        $response = $controller->settle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}
