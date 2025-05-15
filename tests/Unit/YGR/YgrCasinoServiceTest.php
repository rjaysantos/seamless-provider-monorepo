<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Libraries\Randomizer;
use App\GameProviders\V2\Ygr\YgrApi;
use App\GameProviders\V2\Ygr\YgrRepository;
use App\GameProviders\V2\Ygr\YgrCredentials;
use App\GameProviders\V2\Ygr\YgrCasinoService;
use App\GameProviders\V2\Ygr\Contracts\ICredentials;
use App\Exceptions\Casino\TransactionNotFoundException;

class YgrCasinoServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $randomizer = null
    ): YgrCasinoService {
        $repository ??= $this->createStub(YgrRepository::class);
        $credentials ??= $this->createStub(YgrCredentials::class);
        $api ??= $this->createStub(YgrApi::class);
        $randomizer ??= $this->createStub(Randomizer::class);

        return new YgrCasinoService(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            randomizer: $randomizer
        );
    }

    public function test_getLaunchUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameId'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with($request->playId);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('launch')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameId'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(
                playID: $request->playId,
                username: $request->username,
                currency: $request->currency
            );

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('launch')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createPlayGame()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameId'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $mockRepository->expects($this->once())
            ->method('createPlayGame')
            ->with(playID: $request->playId, token: 'testToken', status: $request->gameId);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('launch')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi, randomizer: $stubRandomizer);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameId'
        ]);

        $mockCredentials = $this->createMock(YgrCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($request->currency);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('launch')
            ->willReturn('testUrl.com');

        $service = $this->makeService(credentials: $mockCredentials, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_getGameLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameId'
        ]);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(YgrApi::class);
        $mockApi->expects($this->once())
            ->method('launch')
            ->with(credentials: $providerCredentials, token: 'testToken')
            ->willReturn('testUrl.com');

        $service = $this->makeService(credentials: $stubCredentials, randomizer: $stubRandomizer, api: $mockApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expectedData()
    {
        $expected = 'testUrl.com';

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameId'
        ]);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('launch')
            ->willReturn('testUrl.com');

        $service = $this->makeService(api: $stubApi);
        $response = $service->getLaunchUrl(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_getBetDetail_mockRepository_getTransactionByTransactionID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->expects($this->once())
            ->method('getTransactionByTransactionID')
            ->with(transactionID: $request->bet_id)
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('getBetDetailUrl')
            ->willReturn('testVisual.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_stubRepository_TransactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getTransactionByTransactionID')
            ->willReturn(null);

        // $stubApi = $this->createMock(YgrApi::class);
        // $stubApi->method('getBetDetailUrl')
        //     ->willReturn('testVisual.com');

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getTransactionByTransactionID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $mockCredentials = $this->createMock(YgrCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('getBetDetailUrl')
            ->willReturn('testVisual.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi, credentials: $mockCredentials);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockApi_getBetDetailUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getTransactionByTransactionID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockApi = $this->createMock(YgrApi::class);
        $mockApi->expects($this->once())
            ->method('getBetDetailUrl')
            ->with(credentials: $stubProviderCredentials, transactionID: 'testTransactionID')
            ->willReturn('testVisual.com');

        $service = $this->makeService(repository: $stubRepository, api: $mockApi, credentials: $stubCredentials);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_stubApi_expectedData()
    {
        $expected = 'testVisual.com';

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getTransactionByTransactionID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('getBetDetailUrl')
            ->willReturn('testVisual.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $response = $service->getBetDetail(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}