<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Libraries\Randomizer;
use App\GameProviders\V2\PLA\PlaApi;
use App\GameProviders\V2\PLA\PlaCasinoService;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\GameProviders\V2\PCA\Contracts\IRepository;
use App\GameProviders\V2\PLA\Credentials\PlaStaging;
use App\Exceptions\Casino\TransactionNotFoundException;
use App\GameProviders\V2\PCA\Contracts\ICredentialSetter;

class PlaCasinoServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $randomizer = null
    ): PlaCasinoService {
        $repository ??= $this->createStub(IRepository::class);
        $credentials ??= $this->createStub(ICredentialSetter::class);
        $api ??= $this->createStub(PlaApi::class);
        $randomizer ??= $this->createStub(Randomizer::class);

        return new PlaCasinoService(
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
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with($request->playId);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('getGameLaunchUrl')
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
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(
                $request->playId,
                $request->currency,
                $request->username
            );

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockCredentials = $this->createMock(ICredentialSetter::class);

        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($request->currency);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(credentials: $mockCredentials, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createOrUpdateToken()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(PlaStaging::class);
        $providerCredentials->method('getKioskName')
            ->willReturn('testKioskName');

        $stubCredentials = $this->createMock(ICredentialSetter::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->expects($this->once())
            ->method('createOrUpdateToken')
            ->with($request->playId, 'testKioskName_testToken');

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            api: $stubApi,
            randomizer: $stubRandomizer
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_getGameLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(PlaStaging::class);
        $providerCredentials->method('getKioskName')
            ->willReturn('testKioskName');

        $stubCredentials = $this->createMock(ICredentialSetter::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->expects($this->once())
            ->method('getGameLaunchUrl')
            ->with($providerCredentials, $request, 'testKioskName_testToken')
            ->willReturn('testUrl.com');

        $service = $this->makeService(credentials: $stubCredentials, api: $stubApi, randomizer: $stubRandomizer);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expectedData()
    {
        $expected = 'testUrl.com';

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(api: $stubApi);
        $response = $service->getLaunchUrl(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_getBetDetail_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with($request->play_id)
            ->willReturn((object) []);

        $mockRepository->method('getTransactionByRefID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_stubRepository_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockRepository_getTransactionByRefID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByRefID')
            ->with($request->bet_id)
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_stubRepository_transactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn(null);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $mockCredentials = $this->createMock(ICredentialSetter::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($request->currency);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockApi_gameRoundStatus()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $providerCredentials = $this->createMock(PlaStaging::class);

        $stubCredentials = $this->createMock(ICredentialSetter::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(PlaApi::class);
        $mockApi->expects($this->once())
            ->method('gameRoundStatus')
            ->with($providerCredentials, 'testTransactionID')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, api: $mockApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_stubApi_expectedData()
    {
        $expected = 'testUrl.com';

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $response = $service->getBetDetail(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}
