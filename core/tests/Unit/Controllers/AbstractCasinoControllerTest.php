<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Http\Controllers\AbstractCasinoController;
use App\Exceptions\Casino\InvalidBearerTokenException;
use App\Exceptions\Casino\InvalidCasinoRequestException;

class FakeServiceClass {
    public function getLaunchUrl(Request $request) {}
}

class FakeResponseClass
{
    public function casinoSuccess(string $data)  {}
}

class AbstractCasinoControllerTest extends TestCase
{
    private function makeController($service = null, $response = null)
    {
        $service ??= $this->createMock(FakeServiceClass::class);
        $response ??= $this->createMock(FakeResponseClass::class);

        return new class($service, $response) extends AbstractCasinoController {
            protected $service;
            protected $response;

            public function __construct($service, $response)
            {
                $this->service = $service;
                $this->response = $response;
            }
        };
    }

    #[DataProvider('playParams')]
    public function test_play_missingRequestParameter_invalidCasinoRequestException($parameter)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1'
        ]);

        unset($request[$parameter]);

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    #[DataProvider('playParams')]
    public function test_play_invalidRequestType_invalidCasinoRequestException($parameter, $data)
    {
        $this->expectException(InvalidCasinoRequestException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1'
        ]);

        $request[$parameter] = $data;

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public static function playParams()
    {
        return [
            ['playId', 123],
            ['memberId', 'test'],
            ['username', 123],
            ['host', 123],
            ['currency', 123],
            ['device', 'test'],
            ['gameId', 123],
            ['memberIp', 123],
        ];
    }

    public function test_play_invalidBearerToken_invalidBearerTokenException()
    {
        $this->expectException(InvalidBearerTokenException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . 'invalidBearerToken');

        $controller = $this->makeController();
        $controller->play(request: $request);
    }

    public function test_play_mockService_getLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . config('app.bearer'));

        $mockService = $this->createMock(FakeServiceClass::class);
        $mockService->expects($this->once())
            ->method('getLaunchUrl')
            ->with(request: $request)
            ->willReturn('test url');

        $controller = $this->makeController(service: $mockService);
        $controller->play(request: $request);
    }

    public function test_play_mockResponse_casinoSuccess()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1'
        ]);
        $request->headers->set('Authorization', 'Bearer ' . config('app.bearer'));

        $stubService = $this->createMock(FakeServiceClass::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testUrl.com');

        $mockResponse = $this->createMock(FakeResponseClass::class);
        $mockResponse->expects($this->once())
            ->method('casinoSuccess')
            ->with( 'testUrl.com');

        $controller = $this->makeController(
            service: $stubService,
            response: $mockResponse
        );
        $controller->play(request: $request);
    }

    public function test_play_stubResponse_expectedData()
    {
        $expected = 'response';

        $request = new Request([
            'playId' => 'testPlayID',
            'memberId' => 123,
            'username' => 'testUsername',
            'host' => 'testHost.com',
            'currency' => 'IDR',
            'device' => 0,
            'gameId' => 'testGameID',
            'memberIp' => '127.0.0.1'
        ]);

        $request->headers->set('Authorization', 'Bearer ' . config('app.bearer'));

        $stubService = $this->createMock(FakeServiceClass::class);
        $stubService->method('getLaunchUrl')
            ->willReturn('testUrl.com');

        $stubResponse = $this->createMock(FakeResponseClass::class);
        $stubResponse->method('casinoSuccess')
            ->willReturn($expected);

        $controller = $this->makeController(service: $stubService, response: $stubResponse);
        $response = $controller->play(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }
}