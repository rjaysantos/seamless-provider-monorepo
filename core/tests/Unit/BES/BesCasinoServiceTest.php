<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\GameProviders\V2\Bes\BesApi;
use App\GameProviders\V2\Bes\BesRepository;
use App\GameProviders\V2\Bes\BesCredentials;
use App\GameProviders\V2\Bes\BesCasinoService;
use App\GameProviders\V2\Bes\Contracts\ICredentials;

class BesCasinoServiceTest extends TestCase
{
    public function makeService($repository = null, $credentials = null, $api = null)
    {
        $repository ??= $this->createMock(BesRepository::class);
        $credentials ??= $this->createMock(BesCredentials::class);
        $api ??= $this->createMock(BesApi::class);

        return new BesCasinoService($repository, $credentials, $api);
    }

    public function test_getGameUrl_DBPlayerEmpty_MockRepoCreatePlayer()
    {
        $request = new Request([
            'playId' => 'test-play-id',
            'username' => 'test-username',
            'currency' => 'test-currency',
            'language' => 'en'
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->expects($this->exactly(1))
            ->method('createPlayer')
            ->with('test-play-id', 'test-username', 'test-currency');

        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $mockRepository);
        $service->getGameUrl($request);
    }

    public function test_getGameUrl_DBPlayerNotEmpty_MockRepoCreatePlayerNotCalled()
    {
        $request = new Request([
            'playId' => 'test-play-id',
            'username' => 'test-username',
            'currency' => 'test-currency',
            'language' => 'en'
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->expects($this->exactly(0))
            ->method('createPlayer')
            ->with('test-play-id', 'test-username', 'test-currency');

        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object)['test']);

        $service = $this->makeService(repository: $mockRepository);
        $service->getGameUrl($request);
    }

    public function test_getGameUrl_languageCN_expected()
    {
        $expected = 'gameUrl&aid=&lang=zh';

        $request = new Request([
            'playId' => 'test-play-id',
            'username' => 'test-username',
            'currency' => 'test-currency',
            'language' => 'cn'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object)['test']);

        $stubApi = $this->createMock(BesApi::class);
        $stubApi->method('getKey')
            ->willReturn('gameUrl');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $result = $service->getGameUrl($request);

        $this->assertSame($expected, $result);
    }

    public function test_getGameUrl_languageVN_expected()
    {
        $expected = 'gameUrl&aid=&lang=vi';

        $request = new Request([
            'playId' => 'test-play-id',
            'username' => 'test-username',
            'currency' => 'test-currency',
            'language' => 'vn'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object)['test']);

        $stubApi = $this->createMock(BesApi::class);
        $stubApi->method('getKey')
            ->willReturn('gameUrl');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $result = $service->getGameUrl($request);

        $this->assertSame($expected, $result);
    }

    public function test_updateGamePosition_givenGameListResponseMockNavigationApi_updateGamePosition()
    {
        $credentials = $this->createMock(ICredentials::class);

        $expectedGameCodes = ['test3', 'test2', 'test1'];

        $mockBesApi = $this->createMock(BesApi::class);
        $mockBesApi->expects($this->once())
            ->method('updateGamePosition')
            ->with($credentials, $expectedGameCodes);

        $mockBesApi->method('getGameList')
            ->willReturn([
                (object)[
                    'gid' => 'test1',
                    'SortID' => 3
                ],
                (object)[
                    'gid' => 'test2',
                    'SortID' => 2
                ],
                (object)[
                    'gid' => 'test3',
                    'SortID' => 1
                ],
            ]);

        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(credentials: $stubCredentials, api: $mockBesApi);
        $service->updateGamePosition();
    }
}
