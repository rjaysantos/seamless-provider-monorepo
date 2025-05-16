<?php

use Tests\TestCase;
use Providers\Bes\BesApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Bes\BesService;
use Providers\Bes\BesRepository;
use Providers\Bes\BesCredentials;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Bes\Contracts\ICredentials;
use Providers\Bes\Exceptions\WalletException;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Bes\Exceptions\PlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Bes\Exceptions\InsufficientFundException;
use Providers\Bes\Exceptions\TransactionAlreadyExistsException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;

class BesServiceTest extends TestCase
{
    public function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $wallet = null,
        $walletReport = null
    ): BesService {
        $repository ??= $this->createMock(BesRepository::class);
        $credentials ??= $this->createMock(BesCredentials::class);
        $api ??= $this->createMock(BesApi::class);
        $wallet ??= $this->createMock(IWallet::class);
        $walletReport ??= $this->createMock(WalletReport::class);

        return new BesService(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            wallet: $wallet,
            walletReport: $walletReport
        );
    }

    public function test_getLaunchUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId);

        $service = $this->makeService(repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['test']);

        $mockRepository->expects($this->exactly(0))
            ->method('createPlayer')
            ->with(
                playID: $request->playId,
                username: $request->username,
                currency: $request->currency
            );

        $service = $this->makeService(repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        $mockCredentials = $this->createMock(BesCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $service = $this->makeService(credentials: $mockCredentials);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_getKey()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => 'en',
            'host' => 'testHost'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(BesApi::class);
        $mockApi->expects($this->once())
            ->method('getKey')
            ->with(
                credentials: $providerCredentials,
                playID: $request->playId
            )
            ->willReturn('testLaunchUrl.com');

        $service = $this->makeService(credentials: $stubCredentials, api: $mockApi);
        $service->getLaunchUrl(request: $request);
    }

    #[DataProvider('languageParams')]
    public function test_getLaunchUrl_stubApiMultipleLanguage_expectedData($param, $expectedLang)
    {
        $expected = "testLaunchUrl&aid=&gid=1&lang={$expectedLang}&return_url=testHost";

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => '1',
            'language' => $param,
            'host' => 'testHost'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object)['test']);

        $stubApi = $this->createMock(BesApi::class);
        $stubApi->method('getKey')
            ->willReturn('testLaunchUrl');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $result = $service->getLaunchUrl(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }

    public static function languageParams()
    {
        return [
            ['cn', 'zh'],
            ['vn', 'vi']
        ];
    }

    public function test_getBetDetailUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->play_id)
            ->willReturn((object) []);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testRoundID-testTransID']);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepositoryNullPlayer_PlayerNotFoundException()
    {
        $this->expectException(CasinoPlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->bet_id)
            ->willReturn((object) ['trx_id' => 'testRoundID-testTransID']);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepositoryNullPlayer_TransactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testRoundID-testTransID']);

        $mockCredentials = $this->createMock(BesCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockApi_getDetailsUrl()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testRoundID-testTransID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(BesApi::class);
        $mockApi->expects($this->once())
            ->method('getDetailsUrl')
            ->with(
                credentials: $providerCredentials,
                transactionID: 'testTransID'
            );

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, api: $mockApi);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubApi_expectedData()
    {
        $expectedData = 'testVisualUrl.com';

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRoundID-testTransID',
            'currency' => 'IDR',
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testRoundID-testTransID']);

        $stubApi = $this->createMock(BesApi::class);
        $stubApi->method('getDetailsUrl')
            ->willReturn('testVisualUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $response = $service->getBetDetailUrl(request: $request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_updateGamePosition_mockCredentials_getCredentialsByCurrency()
    {
        $mockCredentials = $this->createMock(BesCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubApi = $this->createMock(BesApi::class);
        $stubApi->method('getGameList')
            ->willReturn([
                (object) [
                    'gid' => 'test1',
                    'SortID' => 3
                ],
                (object) [
                    'gid' => 'test2',
                    'SortID' => 2
                ],
                (object) [
                    'gid' => 'test3',
                    'SortID' => 1
                ]
            ]);

        $service = $this->makeService(credentials: $mockCredentials, api: $stubApi);
        $service->updateGamePosition();
    }

    public function test_updateGamePosition_mockBesApi_getGameList()
    {
        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(BesApi::class);
        $mockApi->expects($this->once())
            ->method('getGameList')
            ->with(credentials: $providerCredentials)
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

        $service = $this->makeService(credentials: $stubCredentials, api: $mockApi);
        $service->updateGamePosition();
    }

    public function test_updateGamePosition_mockNavigationApi_updateGamePosition()
    {
        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(BesApi::class);
        $mockApi->method('getGameList')
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

        $mockApi->expects($this->once())
            ->method('updateGamePosition')
            ->with(credentials: $providerCredentials, gameCodes: ['test3', 'test2', 'test1']);

        $service = $this->makeService(credentials: $stubCredentials, api: $mockApi);
        $service->updateGamePosition();
    }

    public function test_updateGamePosition_stubApi_expectedData()
    {
        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(BesApi::class);
        $stubApi->method('getGameList')
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

        $service = $this->makeService(credentials: $stubCredentials, api: $stubApi);
        $result = $service->updateGamePosition();

        $this->assertNull(actual: $result);
    }

    public function test_getBalance_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'action' => 1
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object)[]);

        $stubWallet = $this->createStub(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_playerNotFound_PlayerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'action' => 1
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'action' => 1
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object)[]);

        $credentials = $this->createMock(ICredentials::class);
        $mockCredentials = $this->createMock(BesCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency)
            ->willReturn($credentials);

        $stubWallet = $this->createStub(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockWallet_balance()
    {
        $request = new Request([
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'action' => 1
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object)[]);

        $credentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $credentials,
                playID: $request->uid
            )
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $mockWallet, credentials: $stubCredentials);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_walletStatusCodeNot2100_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'action' => 1
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['test']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 9999]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWallet_expectedData()
    {
        $request = new Request([
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'action' => 1
        ]);

        $expected = 1000.0;

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['test']);

        $credentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createStub(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, credentials: $stubCredentials);
        $result = $service->getBalance(request: $request);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_settleBet_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'uid' => 'testUid',
            'roundId' => 'test',
            'bet' => 10.0,
            'gid' => 'testGID',
            'win' => 10.0
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) [
                'currency' => 'IDR'
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWalletResponse = $this->createMock(IWallet::class);
        $stubWalletResponse->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $stubWalletResponse->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWalletResponse,
            walletReport: $stubReport
        );

        $service->settleBet(request: $request);
    }

    public function test_settleBet_stubRepositoryNullPlayer_PlayerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'uid' => 'test-uid'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settleBet(request: $request);
    }

    public function test_settleBet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'uid' => 'testUid',
            'roundId' => 'test',
            'bet' => 10.0,
            'gid' => 'testGID',
            'win' => 10.0,
            'transId' => 'testTransId'
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: "{$request->roundId}-{$request->transId}");

        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR'
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWalletResponse = $this->createMock(IWallet::class);
        $stubWalletResponse->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $stubWalletResponse->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWalletResponse,
            walletReport: $stubReport
        );

        $service->settleBet(request: $request);
    }

    public function test_settleBet_stubRepositoryRoundIdAndTransIdExist_TransactionAlreadyExistsException()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request([
            'uid' => 'testUid',
            'roundId' => 'test',
            'bet' => 10.0,
            'gid' => 'testGID',
            'win' => 10.0,
            'transId' => 'testTransId'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(collect());

        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(collect(['test']));

        $service = $this->makeService(repository: $stubRepository);
        $service->settleBet(request: $request);
    }

    public function test_settleBet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'uid' => 'testUid',
            'roundId' => 'test',
            'bet' => 10.0,
            'gid' => 'testGID',
            'win' => 10.0,
            'transId' => 'testTransId'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(BesCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWalletResponse = $this->createMock(IWallet::class);
        $stubWalletResponse->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $stubWalletResponse->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWalletResponse,
            walletReport: $stubReport
        );

        $service->settleBet(request: $request);
    }

    public function test_settleBet_mockWallet_balance()
    {
        $request = new Request([
            'uid' => 'testUid',
            'roundId' => 'test',
            'bet' => 10.0,
            'gid' => 'testGID',
            'win' => 10.0,
            'transId' => 'testTransId'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR'
            ]);

        $credentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $mockWalletResponse = $this->createMock(IWallet::class);
        $mockWalletResponse->expects($this->once())
            ->method('balance')
            ->with(
                $credentials,
                $request->uid
            )
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $mockWalletResponse->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWalletResponse,
            walletReport: $stubReport
        );

        $service->settleBet(request: $request);
    }

    public function test_settleBet_walletErrorBalance_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'uid' => 'test-uid',
            'roundId' => 'test',
            'win' => 10.0,
            'gid' => 'test-gid',
            'ts' => 123465,
            'bet' => 10.0
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 'invalid status code'
            ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(
                (object) [
                    'currency' => 'IDR'
                ]
            );


        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet,);
        $service->settleBet(request: $request);
    }

    public function test_settleBet_walletBalanceNotEnough_InsufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'uid' => 'test-uid',
            'roundId' => 'test',
            'win' => 10.0,
            'gid' => 'test-gid',
            'ts' => 123465,
            'bet' => 10.0
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 0,
                'status_code' => 2100
            ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(
                (object) [
                    'currency' => 'test-currency'
                ]
            );

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet,);
        $service->settleBet(request: $request);
    }

    public function test_settleBet_mockRepository_createTransaction()
    {
        $request = new Request([
            'uid' => 'testUid',
            'roundId' => 'test',
            'bet' => 10.0,
            'gid' => 'testGID',
            'win' => 10.0,
            'transId' => 'testTransId'
        ]);

        $mockRepository = $this->createMock(BesRepository::class);
        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                transactionID: "{$request->roundId}-{$request->transId}",
                betAmount: $request->bet,
                winAmount: $request->win
            );

        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR'
            ]);

        $credentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $mockWalletResponse = $this->createMock(IWallet::class);
        $mockWalletResponse->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $mockWalletResponse->method('wagerAndPayout')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $mockWalletResponse,
            walletReport: $stubWalletReport
        );

        $service->settleBet(request: $request);
    }

    public function test_settleBet_mockWalletReport_makeSlotReprot()
    {
        $request = new Request([
            'uid' => 'testUid',
            'roundId' => 'test',
            'bet' => 10.0,
            'gid' => 'testGID',
            'win' => 10.0,
            'transId' => 'testTransId',
            'ts' => 1746720000000
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testUid',
                'currency' => 'IDR'
            ]);

        $credentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($credentials);

        $stubWalletResponse = $this->createMock(IWallet::class);
        $stubWalletResponse->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $stubWalletResponse->method('wagerAndPayout')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: "{$request->roundId}-{$request->transId}",
                gameCode: $request->gid,
                betTime: '2025-05-09 00:00:00'
            )
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWalletResponse,
            walletReport: $mockWalletReport
        );

        $service->settleBet(request: $request);
    }

    public function test_settleBet_mockWallet_wagerAndPayout()
    {
        $request = new Request([
            'uid' => 'testUid',
            'roundId' => 'test',
            'bet' => 10.0,
            'gid' => 'testGID',
            'win' => 10.0,
            'transId' => 'testTransId'
        ]);

        $report = new Report;

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testUid',
                'currency' => 'IDR'
            ]);

        $credentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(BesCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $mockWalletResponse = $this->createMock(IWallet::class);
        $mockWalletResponse->expects($this->once())
            ->method('wagerAndPayout')
            ->with(
                credentials: $credentials,
                playID: $request->uid,
                currency: 'IDR',
                wagerTransactionID: "{$request->roundId}-{$request->transId}",
                wagerAmount: $request->bet,
                payoutTransactionID: "{$request->roundId}-{$request->transId}",
                payoutAmount: $request->win,
                report: $report
            )
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $mockWalletResponse->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWalletResponse,
            walletReport: $stubReport
        );

        $service->settleBet(request: $request);
    }

    public function test_settleBet_wagerAndPayoutCodeNotSuccess_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'uid' => 'test-uid',
            'roundId' => 'test',
            'win' => 10.0,
            'gid' => 'test-gid',
            'ts' => 123465,
            'bet' => 10.0
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 'invalid status code'
            ]);

        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(
                (object) [
                    'currency' => 'test-currency'
                ]
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );

        $service->settleBet(request: $request);
    }

    public function test_settleBet_stubWallet_expectedData()
    {
        $request = new Request([
            'uid' => 'test-uid',
            'roundId' => 'test',
            'win' => 10.0,
            'gid' => 'test-gid',
            'ts' => 123465,
            'bet' => 10.0
        ]);

        $expected = (object)[
            'balance' => 100,
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object)[
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100
            ]);

        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );

        $response = $service->settleBet(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }
}
