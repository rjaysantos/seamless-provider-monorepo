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

    #[DataProvider('deductInvalidParams')]
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

    public static function deductInvalidParams()
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
            ->with($request);

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