<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use App\GameProviders\V2\Ygr\YgrRepository;
use App\GameProviders\V2\Ygr\YgrCredentials;
use App\GameProviders\V2\Ygr\YgrProviderService;
use App\GameProviders\V2\Ygr\Credentials\YgrStaging;
use App\GameProviders\V2\Ygr\Exceptions\WalletErrorException;
use App\GameProviders\V2\Ygr\Exceptions\TokenNotFoundException;
use App\GameProviders\V2\Ygr\Exceptions\InsufficientFundException;
use App\GameProviders\V2\Ygr\Exceptions\TransactionAlreadyExistsException;

class YgrProviderServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $wallet = null,
        $walletReport = null
    ): YgrProviderService {
        $repository ??= $this->createMock(YgrRepository::class);
        $credentials ??= $this->createMock(YgrCredentials::class);
        $wallet ??= $this->createMock(IWallet::class);
        $walletReport ??= $this->createMock(WalletReport::class);

        return new YgrProviderService(
            repository: $repository,
            credentials: $credentials,
            wallet: $wallet,
            walletReport: $walletReport
        );
    }

    public function test_getPlayerDetails_mockRepository_getPlayerByToken()
    {
        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByToken')
            ->with(token: $request->connectToken)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'status' => 'testGameID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $makeProviderService = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $makeProviderService->getPlayerDetails(request: $request);
    }

    public function test_getPlayerDetails_stubRepository_TokenDataDoesNotExistException()
    {
        $this->expectException(TokenNotFoundException::class);

        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn(null);

        $makeProviderService = $this->makeService(repository: $stubRepository);
        $makeProviderService->getPlayerDetails(request: $request);
    }

    public function test_getPlayerDetails_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'status' => 'testGameID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(YgrCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $makeProviderService = $this->makeService(repository: $stubRepository, wallet: $stubWallet, credentials: $mockCredentials);
        $makeProviderService->getPlayerDetails(request: $request);
    }

    public function test_getPlayerDetails_mockWallet_balance()
    {
        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'status' => 'testGameID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(YgrStaging::class);
        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $stubProviderCredentials, playID: 'testPlayID')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $makeProviderService = $this->makeService(repository: $stubRepository, wallet: $mockWallet, credentials: $stubCredentials);
        $makeProviderService->getPlayerDetails(request: $request);
    }

    public function test_getPlayerDetails_stubWallet_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'status' => 'testGameID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 0,
            ]);

        $makeProviderService = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $makeProviderService->getPlayerDetails(request: $request);
    }

    public function test_getPlayerDetails_stubWallet_expectedData()
    {
        $expected = (object) [
            'ownerId' => 'testOwnerID',
            'parentId' => 'testParentID',
            'gameId' => 'testGameID',
            'userId' => 'testPlayID',
            'nickname' => 'testUsername',
            'currency' => 'IDR',
            'balance' => 100.00
        ];

        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'status' => 'testGameID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(YgrStaging::class);
        $stubProviderCredentials->method('getOwnerID')
            ->willReturn('testOwnerID');
        $stubProviderCredentials->method('getParentID')
            ->willReturn('testParentID');

        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $makeProviderService = $this->makeService(repository: $stubRepository, wallet: $stubWallet, credentials: $stubCredentials);
        $response = $makeProviderService->getPlayerDetails(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }
    public function test_deleteToken_mockRepository_getPlayerByToken()
    {
        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByToken')
            ->with($request->connectToken)
            ->willReturn((object) ['token' => 'testToken']);

        $service = $this->makeService(repository: $mockRepository);
        $service->deleteToken(request: $request);
    }

    public function test_deleteToken_stubRepository_TokenNotFoundException()
    {
        $this->expectException(TokenNotFoundException::class);

        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->deleteToken(request: $request);
    }

    public function test_deleteToken_mockRepository_deletePlayGameByToken()
    {
        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $mockRepository = $this->createMock(YgrRepository::class);
        $mockRepository->method('getPlayerByToken')
            ->willReturn((object) ['token' => 'testToken']);

        $mockRepository->expects($this->once())
            ->method('deletePlayGameByToken')
            ->with($request->connectToken);

        $service = $this->makeService(repository: $mockRepository);
        $service->deleteToken(request: $request);
    }

    public function test_deleteToken_stubRepository_expectedData()
    {
        $request = new Request([
            'connectToken' => 'testToken'
        ]);

        $stubRepository = $this->createMock(YgrRepository::class);
        $stubRepository->expects($this->once())
            ->method('getPlayerByToken')
            ->with($request->connectToken)
            ->willReturn((object) ['token' => 'testToken']);

        $service = $this->makeService(repository: $stubRepository);
        $response = $service->deleteToken(request: $request);

        $this->assertNull($response);
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

    public function test_betAndSettle_mockRepository_getTransactionByTransactionID()
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
            ->method('getTransactionByTransactionID')
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
        $stubRepository->method('getTransactionByTransactionID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $service = $this->makeService(repository: $stubRepository);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockCredentials_getCredentialsByCurrency()
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
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

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

        $stubProviderCredentials = $this->createMock(YgrStaging::class);
        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
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
        $stubRepository->method('getTransactionByTransactionID')
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

        $stubProviderCredentials = $this->createMock(YgrStaging::class);
        $stubCredentials = $this->createMock(YgrCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
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