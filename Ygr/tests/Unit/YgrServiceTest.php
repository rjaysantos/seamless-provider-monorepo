<?php

use Tests\TestCase;
use Providers\Ygr\YgrApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Providers\Ygr\YgrService;
use Providers\Ygr\YgrRepository;
use Providers\Ygr\YgrCredentials;
use Providers\Ygr\DTO\YgrPlayerDTO;
use Providers\Ygr\DTO\YgrRequestDTO;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Ygr\Contracts\ICredentials;
use Providers\Ygr\Exceptions\WalletErrorException;
use Providers\Ygr\Exceptions\TokenNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Ygr\Exceptions\InsufficientFundException;
use Providers\Ygr\Exceptions\TransactionAlreadyExistsException;

class YgrServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $randomizer = null,
        $wallet = null,
        $walletReport = null
    ): YgrService {
        $repository ??= $this->createStub(YgrRepository::class);
        $credentials ??= $this->createStub(YgrCredentials::class);
        $api ??= $this->createStub(YgrApi::class);
        $randomizer ??= $this->createStub(Randomizer::class);
        $wallet ??= $this->createMock(IWallet::class);
        $walletReport ??= $this->createMock(WalletReport::class);

        return new YgrService(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            randomizer: $randomizer,
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
            'gameId' => 'testGameId',
            'language' => 'id'
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
            'gameId' => 'testGameId',
            'language' => 'id'
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

    public function test_getLaunchUrl_mockRepository_createOrUpdatePlayGame()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameId',
            'language' => 'id'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $mockRepository->expects($this->once())
            ->method('createOrUpdatePlayGame')
            ->with(playID: $request->playId, token: 'testToken', status: $request->gameId);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('launch')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi, randomizer: $stubRandomizer);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentials()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameId',
            'language' => 'id'
        ]);

        $mockCredentials = $this->createMock(YgrCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentials');

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
            'gameId' => 'testGameId',
            'language' => 'id'
        ]);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentials')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(YgrApi::class);
        $mockApi->expects($this->once())
            ->method('launch')
            ->with(credentials: $providerCredentials, token: 'testToken', language: 'id')
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
            'gameId' => 'testGameId',
            'language' => 'id'
        ]);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('launch')
            ->willReturn('testUrl.com');

        $service = $this->makeService(api: $stubApi);
        $response = $service->getLaunchUrl(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_getBetDetail_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
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
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockCredentials_getCredentials()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'txn_id' => null,
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $mockCredentials = $this->createMock(YgrCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentials');

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
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentials')
            ->willReturn($stubProviderCredentials);

        $mockApi = $this->createMock(YgrApi::class);
        $mockApi->expects($this->once())
            ->method('getBetDetailUrl')
            ->with(
                credentials: $stubProviderCredentials,
                transactionID: 'testTransactionID',
                currency: 'IDR'
            )
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
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $stubApi = $this->createMock(YgrApi::class);
        $stubApi->method('getBetDetailUrl')
            ->willReturn('testVisual.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $response = $service->getBetDetail(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_getPlayerDetails_mockWallet_balance()
    {
        $requestDTO = new YgrRequestDTO(token: 'testToken');

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn(new YgrPlayerDTO(
                playID: 'testPlayID',
                gameCode: 'testGameID',
                username: 'testUsername',
                currency: 'IDR'
            ));

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentials')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $stubProviderCredentials, playID: 'testPlayID')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $makeProviderService = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $makeProviderService->getPlayerDetails(requestDTO: $requestDTO);
    }

    public function test_betAndSettle_mockRepository_getPlayerByToken()
    {
        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByToken')
            ->with(token: $request->connectToken)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, walletReport: $stubReport);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRepository_TokenNotFoundException()
    {
        $this->expectException(TokenNotFoundException::class);

        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);
        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->roundID);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, walletReport: $stubReport);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRepository_TransactionAlreadyExistsException()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $service = $this->makeService(repository: $stubRepository);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockCredentials_getCredentials()
    {
        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);

        $mockCredentials = $this->createMock(YgrCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentials');

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubReport,
            credentials: $mockCredentials
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWallet_balance()
    {
        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentials')
            ->willReturn($stubProviderCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $stubProviderCredentials, playID: 'testPlayID')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);
        $mockWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            walletReport: $stubReport,
            credentials: $stubCredentials
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletBalance_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, walletReport: $stubReport);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWallet_InsufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, walletReport: $stubReport);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockRepository_createTransaction()
    {
        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                transactionID: $request->roundID,
                betAmount: $request->betAmount,
                winAmount: $request->payoutAmount,
                transactionDate: '2021-01-01 00:00:00'
            );

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, walletReport: $stubReport);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWalletReport_makeSlotReport()
    {
        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: $request->roundID,
                gameCode: 'testGameID',
                betTime: '2021-01-01 00:00:00'
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, walletReport: $mockReport);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWallet_wagerAndPayout()
    {
        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentials')
            ->willReturn($stubProviderCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);
        $mockWallet->expects($this->once())
            ->method('wagerAndPayout')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                wagerTransactionID: $request->roundID,
                wagerAmount: $request->betAmount,
                payoutTransactionID: $request->roundID,
                payoutAmount: $request->payoutAmount,
                report: new Report,
            )
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            walletReport: $stubReport,
            credentials: $stubCredentials
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletWagerAndPayout_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 3120
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, walletReport: $stubReport);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWallet_expectedData()
    {
        $expected = (object) [
            'balance' => 1000.00,
            'currency' => 'IDR'
        ];

        $request = new Request([
            'connectToken' => 'testToken',
            'roundID' => 'testTransactionID',
            'betAmount' => 100.00,
            'payoutAmount' => 300.00,
            'freeGame' => 0,
            'wagersTime' => '2021-01-01T00:00:00.123+08:00'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'status' => 'testGameID'
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, walletReport: $stubReport);
        $response = $service->betAndSettle(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }
}
