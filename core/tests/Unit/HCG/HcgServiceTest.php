<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\GameProviders\V2\Hcg\HcgApi;
use App\GameProviders\V2\Hcg\HcgService;
use App\GameProviders\V2\Hcg\HcgRepository;
use App\GameProviders\V2\Hcg\HcgCredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\GameProviders\V2\Hcg\Contracts\ICredentials;
use App\Exceptions\Casino\TransactionNotFoundException;

class HcgServiceTest extends TestCase
{
    private function makeService(
        HcgRepository $repository = null,
        HcgCredentials $credentials = null,
        HcgApi $api = null
    ): HcgService {
        $repository ??= $this->createStub(HcgRepository::class);
        $credentials ??= $this->createStub(HcgCredentials::class);
        $api ??= $this->createStub(HcgApi::class);

        return new HcgService(repository: $repository, credentials: $credentials, api: $api);
    }

    public function test_getLaunchUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId);

        $service = $this->makeService(repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $mockCredentials = $this->createMock(HcgCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $service = $this->makeService(credentials: $mockCredentials);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(playID: $request->playId, username: $request->username, currency: $request->currency);

        $service = $this->makeService(repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_userRegistrationInterface()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(HcgApi::class);
        $mockApi->expects($this->once())
            ->method('userRegistrationInterface')
            ->with(credentials: $providerCredentials, playID: $request->playId);

        $service = $this->makeService(credentials: $stubCredentials, api: $mockApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_userLoginInterface()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(HcgApi::class);
        $mockApi->expects($this->once())
            ->method('userLoginInterface')
            ->with(credentials: $providerCredentials, playID: $request->playId, gameCode: $request->gameId);

        $service = $this->makeService(credentials: $stubCredentials, api: $mockApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expected()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1'
        ]);

        $expected = 'testUrl.com';

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(HcgApi::class);
        $stubApi->method('userLoginInterface')
            ->willReturn($expected);

        $service = $this->makeService(credentials: $stubCredentials, api: $stubApi);
        $result = $service->getLaunchUrl(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }

    public function test_getVisualUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransacID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->play_id)
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getTransactionByTrxID')->willReturn((object) []);

        $service = $this->makeService(repository: $mockRepository);
        $service->getVisualUrl(request: $request);
    }

    public function test_getVisualUrl_stubRepositoryNullPlayer_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransacID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getVisualUrl(request: $request);
    }

    public function test_getVisualUrl_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransacID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->method('getPlayerByPlayID')->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->bet_id)
            ->willReturn((object) []);

        $service = $this->makeService(repository: $mockRepository);
        $service->getVisualUrl(request: $request);
    }

    public function test_getVisualUrl_stubRepositoryNullTransaction_transactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransacID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getVisualUrl(request: $request);
    }

    public function test_getVisualUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransacID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $mockCredentials = $this->createMock(HcgCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials);
        $service->getVisualUrl(request: $request);
    }

    #[DataProvider('formmattedTransactionIDs')]
    public function test_getVisualUrl_stubCredentials_expected($transactionID)
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => $transactionID,
            'currency' => 'IDR'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVisualUrl')->willReturn('https://testUrl.com');
        $providerCredentials->method('getAgentID')->willReturn('1234');

        $expected = "https://testUrl.com/#/order_details/en/1234/testTransacID";

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $response = $service->getVisualUrl(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public static function formmattedTransactionIDs()
    {
        return [
            ['testTransacID'],
            ['1-testTransacID'],
        ];
    }
}