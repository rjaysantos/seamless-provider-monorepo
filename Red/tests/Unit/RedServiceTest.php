<?php

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Red\RedApi;
use Wallet\V1\ProvSys\Transfer\Report;
use Providers\Red\RedService;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Red\RedRepository;
use Providers\Red\RedCredentials;
use App\Exceptions\Casino\WalletErrorException;
use App\Exceptions\Casino\PlayerNotFoundException;
use Providers\Red\Credentials\RedStaging;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Red\Exceptions\InsufficientFundException;
use Providers\Red\Exceptions\InvalidSecretKeyException;
use Providers\Red\Exceptions\BonusTransactionAlreadyExists;
use Providers\Red\Exceptions\TransactionDoesNotExistException;
use Providers\Red\Exceptions\TransactionAlreadyExistsException;
use Providers\Red\Exceptions\TransactionAlreadySettledException;
use Providers\Red\Exceptions\WalletErrorException as ProviderWalletErrorException;
use Providers\Red\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;

class RedServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $wallet = null,
        $walletReport = null
    ): RedService {
        $repository ??= $this->createStub(RedRepository::class);
        $credentials ??= $this->createStub(RedCredentials::class);
        $api ??= $this->createStub(RedApi::class);
        $wallet ??= $this->createStub(IWallet::class);
        $walletReport ??= $this->createStub(WalletReport::class);

        return new RedService(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            wallet: $wallet,
            walletReport: $walletReport
        );
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockCredentials = $this->createMock(RedCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(RedApi::class);
        $stubApi->method('authenticate')
            ->willReturn((object) [
                'userID' => 123,
                'launchUrl' => 'testUrl.com'
            ]);

        $service = $this->makeService(
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $mockCredentials
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockWallet_balance()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->playId
            )
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(RedApi::class);
        $stubApi->method('authenticate')
            ->willReturn((object) [
                'userID' => 123,
                'launchUrl' => 'testUrl.com'
            ]);

        $service = $this->makeService(
            wallet: $mockWallet,
            api: $stubApi,
            credentials: $stubCredentials
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubWallet_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 1234]);

        $service = $this->makeService(wallet: $stubWallet);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_authenticate()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockApi = $this->createMock(RedApi::class);
        $mockApi->expects($this->once())
            ->method('authenticate')
            ->with(
                credentials: $stubProviderCredentials,
                request: $request,
                username: $request->playId,
                balance: 1000.00
            )
            ->willReturn((object) [
                'userID' => 123,
                'launchUrl' => 'testUrl.com'
            ]);

        $service = $this->makeService(
            wallet: $stubWallet,
            api: $mockApi,
            credentials: $stubCredentials
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(RedApi::class);
        $stubApi->method('authenticate')
            ->willReturn((object) [
                'userID' => 123,
                'launchUrl' => 'testUrl.com'
            ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId)
            ->willReturn((object) ['username' => 'testPlayID']);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createOrIgnorePlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(RedApi::class);
        $stubApi->method('authenticate')
            ->willReturn((object) [
                'userID' => 123,
                'launchUrl' => 'testUrl.com'
            ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(
                playID: $request->playId,
                currency: $request->currency,
                userIDProvider: 123
            );

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            api: $stubApi
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expectedData()
    {
        $expected = 'testUrl.com';

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'memberId' => 'testMemberId',
            'host' => 'testHost.com',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(RedApi::class);
        $stubApi->method('authenticate')
            ->willReturn((object) [
                'userID' => 123,
                'launchUrl' => 'testUrl.com'
            ]);

        $service = $this->makeService(
            wallet: $stubWallet,
            api: $stubApi
        );
        $response = $service->getLaunchUrl(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_getBetDetailUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayerID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->play_id)
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn((object) ['ext_id' => 'payout-testTransactionID']);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepository_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayerID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockRepository_getTransactionByExtID()
    {
        $request = new Request([
            'play_id' => 'testPlayerID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->expects($this->once())
            ->method('getTransactionByExtID')
            ->with(transactionID: $request->bet_id)
            ->willReturn((object) ['ext_id' => 'payout-testTransactionID']);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepository_transactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayerID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'play_id' => 'testPlayerID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn((object) ['ext_id' => 'payout-testTransactionID']);

        $mockCredentials = $this->createMock(RedCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials
        );
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockApi_getBetResult()
    {
        $request = new Request([
            'play_id' => 'testPlayerID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn((object) ['ext_id' => 'payout-testTransactionID']);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockApi = $this->createMock(RedApi::class);
        $mockApi->expects($this->once())
            ->method('getBetResult')
            ->with(
                credentials: $stubProviderCredentials,
                transactionID: $request->bet_id
            );

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            api: $mockApi
        );
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubApi_expectedData()
    {
        $expected = 'testVisualUrl.com';

        $request = new Request([
            'play_id' => 'testPlayerID',
            'bet_id' => 'payout-testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn((object) ['ext_id' => 'payout-testTransactionID']);

        $stubApi = $this->createMock(RedApi::class);
        $stubApi->method('getBetResult')
            ->willReturn('testVisualUrl.com');

        $service = $this->makeService(
            repository: $stubRepository,
            api: $stubApi
        );
        $response = $service->getBetDetailUrl(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_getBalance_mockRepository_getPlayerByUserIDProvider()
    {
        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUserIDProvider')
            ->with(userIDProvider: $request->user_id)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubRepository_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(RedCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubCredentials_invalidSecretKeyException()
    {
        $this->expectException(InvalidSecretKeyException::class);

        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);
        $request->headers->set('secret-key', 'invalidSecretKey');

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubProviderCredentials->method('getSecretKey')
            ->willReturn('testSecretKey');
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockWallet_balance()
    {
        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID'
            )
            ->willReturn([
                'credit' => 100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWallet_walletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 968432168]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWallet_expected()
    {
        $expected = 100.00;

        $request = new Request([
            'user_id' => 123456,
            'prd_id' => 789,
            'sid' => 'testSid'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $response = $service->getBalance(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_bet_mockRepository_getPlayerByUserIDProvider()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUserIDProvider')
            ->with(userIDProvider: 123)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 2000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubRepository_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(RedCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 2000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport,
            credentials: $mockCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubCredentials_invalidSecretKeyException()
    {
        $this->expectException(InvalidSecretKeyException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);
        $request->headers->set('secret-key', 'invalidSecretKey');

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubProviderCredentials->method('getSecretKey')
            ->willReturn('testSecretKey');
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: 'testTransactionID')
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 2000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubRepository_transactionAlreadyExistsException()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_balance()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID'
            )
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 2000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport,
            credentials: $stubCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWalletBalance_walletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 978987651
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWallet_insufficientFundsException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_createTransaction()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 2100
            ]);

        $stubRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                transactionID: 'testTransactionID',
                betAmount: $request->amount,
                transactionDate: '2021-01-01 08:00:00'
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 2000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockReport_makeSlotReport()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: '2021-01-01 08:00:00'
            )
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 2000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_wager()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'wager-testTransactionID',
                amount: 1000.00,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 2000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport,
            credentials: $stubCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWallet_walletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn(['status_code' => 984321]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWallet_expected()
    {
        $expected = 2000.00;

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'debit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 3000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 2000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->bet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_settle_mockRepository_getPlayerByUserIDProvider()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUserIDProvider')
            ->with(userIDProvider: $request->user_id)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'updated_at' => null
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubRepository_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(RedCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'updated_at' => null
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubCredentials_invalidSecretKeyException()
    {
        $this->expectException(InvalidSecretKeyException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);
        $request->headers->set('secret-key', 'invalidSecretKey');

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubProviderCredentials->method('getSecretKey')
            ->willReturn('testSecretKey');
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->txn_id)
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'updated_at' => null
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubRepository_transactionDoesNotExistException()
    {
        $this->expectException(TransactionDoesNotExistException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_stubRepository_transactionAlreadySettledException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'updated_at' => '2021-01-01 00:00:00'
            ]);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_settleTransaction()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'updated_at' => null
            ]);

        $mockRepository->expects($this->once())
            ->method('settleTransaction')
            ->with(
                transactionID: 'testTransactionID',
                winAmount: 1000.00,
                transactionDate: '2021-01-01 08:00:00'
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockWalletReport_makeSlotReport()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'updated_at' => null
            ]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: 'testTransactionID',
                gameCode: 456,
                betTime: '2021-01-01 08:00:00'
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $mockWalletReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_payout()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'updated_at' => null
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'payout-testTransactionID',
                amount: 1000.00,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubWallet_walletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'updated_at' => null
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn(['status_code' => 9831568]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubWallet_expected()
    {
        $expected = 1000.00;

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456,
            'credit_time' => '2021-01-01 00:00:00'
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'updated_at' => null
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $response = $service->settle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_bonus_mockRepository_getPlayerByUserIDProvider()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUserIDProvider')
            ->with(userIDProvider: $request->user_id)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->bonus(request: $request);
    }

    public function test_bonus_stubRepository_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->bonus(request: $request);
    }

    public function test_bonus_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(RedCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );
        $service->bonus(request: $request);
    }

    public function test_bonus_stubCredentials_invalidSecretKeyException()
    {
        $this->expectException(InvalidSecretKeyException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);
        $request->headers->set('secret-key', 'invalidSecretKey');

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubProviderCredentials->method('getSecretKey')
            ->willReturn('testSecretKey');
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );
        $service->bonus(request: $request);
    }

    public function test_bonus_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->txn_id);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->bonus(request: $request);
    }

    public function test_bonus_stubRepository_bonusTransactionAlreadyExists()
    {
        $this->expectException(BonusTransactionAlreadyExists::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->bonus(request: $request);
    }

    public function test_bonus_mockRepository_createBonusTransaction()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $mockRepository = $this->createMock(RedRepository::class);
        $mockRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('createBonusTransaction')
            ->with(
                transactionID: $request->txn_id,
                bonusAmount: $request->amount,
                transactionDate: '2021-01-01 00:00:00'
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->bonus(request: $request);

        Carbon::setTestNow();
    }

    public function test_bonus_mockWalletReport_makeBonusReport()
    {
        Carbon::setTestNow('2021-01-01 00:00:00');

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeBonusReport')
            ->with(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: '2021-01-01 00:00:00'
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $mockWalletReport,
            wallet: $stubWallet
        );
        $service->bonus(request: $request);

        Carbon::setTestNow();
    }

    public function test_bonus_mockWallet_bonus()
    {
        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(RedStaging::class);
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('bonus')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'bonus-testTransactionID',
                amount: $request->amount,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->bonus(request: $request);
    }

    public function test_bonus_stubWallet_walletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn(['status_code' => 6315648315]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->bonus(request: $request);
    }

    public function test_bonus_stubWallet_expected()
    {
        $expected = 1000.00;

        $request = new Request([
            'user_id' => 123,
            'amount' => 1000.00,
            'txn_id' => 'testTransactionID',
            'game_id' => 456
        ]);

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $response = $service->bonus(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}
