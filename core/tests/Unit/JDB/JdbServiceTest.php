<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\GameProviders\V2\Jdb\JdbApi;
use Wallet\V1\ProvSys\Transfer\Report;
use App\GameProviders\V2\Jdb\JdbService;
use App\Libraries\Wallet\V2\WalletReport;
use App\GameProviders\V2\Jdb\JdbRepository;
use App\GameProviders\V2\Jdb\JdbCredentials;
use App\Exceptions\Casino\WalletErrorException;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\GameProviders\V2\Jdb\Contracts\ICredentials;
use App\Exceptions\Casino\TransactionNotFoundException;
use App\GameProviders\V2\Jdb\Exceptions\InsufficientFundException;
use App\GameProviders\V2\Jdb\Exceptions\TransactionAlreadyExistException;
use App\GameProviders\V2\Jdb\Exceptions\TransactionAlreadySettledException;
use App\GameProviders\V2\Jdb\Exceptions\WalletErrorException as ProviderWalletErrorException;
use App\GameProviders\V2\Jdb\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\GameProviders\V2\Jdb\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class JdbServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $wallet = null,
        $walletReport = null
    ): JdbService {
        $repository ??= $this->createStub(JdbRepository::class);
        $credentials ??= $this->createStub(JdbCredentials::class);
        $api ??= $this->createStub(JdbApi::class);
        $wallet ??= $this->createStub(IWallet::class);
        $walletReport ??= $this->createStub(WalletReport::class);

        return new JdbService(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            wallet: $wallet,
            report: $walletReport
        );
    }

    public function test_getLaunchUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(JdbApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testLaunchUrl.com');

        $service = $this->makeService(
            wallet: $stubWallet,
            api: $stubApi,
            repository: $mockRepository
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(
                playID: $request->playId,
                username: $request->username,
                currency: $request->currency,
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(JdbApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testLaunchUrl.com');

        $service = $this->makeService(
            wallet: $stubWallet,
            api: $stubApi,
            repository: $mockRepository
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $mockCredentials = $this->createMock(JdbCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(JdbApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testLaunchUrl.com');

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
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
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

        $stubApi = $this->createMock(JdbApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testLaunchUrl.com');

        $service = $this->makeService(
            wallet: $mockWallet,
            api: $stubApi,
            credentials: $stubCredentials
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubWallet_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 74486153]);

        $service = $this->makeService(wallet: $stubWallet);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_getGameLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockApi = $this->createMock(JdbApi::class);
        $mockApi->expects($this->once())
            ->method('getGameLaunchUrl')
            ->with(
                credentials: $stubProviderCredentials,
                request: $request,
                balance: 1000.00,
            )
            ->willReturn('testLaunchUrl.com');

        $service = $this->makeService(
            wallet: $stubWallet,
            api: $mockApi,
            credentials: $stubCredentials
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expected()
    {
        $expected = 'testLaunchUrl.com';

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1,
            'gameId' => '8001'
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(JdbApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testLaunchUrl.com');

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
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->play_id)
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['history_id' => 'testHistoryID']);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepositoryNullPlayer_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->bet_id)
            ->willReturn((object) ['history_id' => 'testHistoryID']);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepositoryNullTransaction_transactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['history_id' => 'testHistoryID']);

        $mockCredentials = $this->createMock(JdbCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials
        );
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockApi_queryGameResult()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['history_id' => 'testHistoryID']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockApi = $this->createMock(JdbApi::class);
        $mockApi->expects($this->once())
            ->method('queryGameResult')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->play_id,
                historyID: 'testHistoryID',
                gameID: $request->game_id
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
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR',
            'game_id' => '12332'
        ]);

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['history_id' => 'testHistoryID']);

        $stubApi = $this->createMock(JdbApi::class);
        $stubApi->method('queryGameResult')
            ->willReturn('testVisualUrl.com');

        $service = $this->makeService(
            repository: $stubRepository,
            api: $stubApi
        );
        $response = $service->getBetDetailUrl(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_getBalance_mockRepository_getPlayerByPlayID()
    {
        $request = (object) [
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubRepositoryNullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = (object) [
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentials_getCredentialsByCurrency()
    {
        $request = (object) [
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockCredentials = $this->createMock(JdbCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockWallet_balance()
    {
        $request = (object) [
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->uid
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWalletInvalidStatus_providerWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 41864531]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubResponse_expected()
    {
        $expected = 100.00;

        $request = (object) [
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $response = $service->getBalance(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_cancelBetAndSettle_mockRepository_getPlayerByPlayID()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet
        );
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_stubRepositoryNullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_mockRepository_getTransactionByTrxID()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->transferId)
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet
        );
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_stubRepositoryNullTransaction_transactionNotFoundException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->transferId)
            ->willReturn((object) ['trx_id' => '123456']);

        $service = $this->makeService(repository: $stubRepository);
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_mockCredentials_getCredentialsByCurrency()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockCredentials = $this->createMock(JdbCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_mockWallet_balance()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->uid
            )
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_stubWalletInvalidStatus_providerWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 987654321]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_stubResponse_expected()
    {
        $expected = 1000.00;

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $response = $service->cancelBetAndSettle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_betAndSettle_mockRepository_getPlayerByPlayID()
    {
        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRepositoryNoPlayerData_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockRepository_getTransactionByTrxID()
    {
        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->transferId)
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRepositoryReturnTransactionData_transactionAlreadyExistException()
    {
        $this->expectException(TransactionAlreadyExistException::class);

        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => '123456']);

        $service = $this->makeService(repository: $stubRepository);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockCredentials_getCredentialsByCurrency()
    {
        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockCredentials = $this->createMock(JdbCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport,
            credentials: $mockCredentials
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWallet_balance()
    {
        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->uid
            )
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            walletReport: $stubWalletReport,
            credentials: $stubCredentials
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletBalanceError_walletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 987654321]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletZeroBalance_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 0.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockRepository_createSettleTransaction()
    {
        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockRepository->expects($this->once())
            ->method('createSettleTransaction')
            ->with(
                transactionID: $request->transferId,
                betAmount: abs($request->bet),
                winAmount: $request->win,
                transactionDate: '2021-01-01 00:00:00',
                historyID: $request->historyId
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockReport_makeArcadeReport()
    {
        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 123,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: $request->transferId,
                gameCode: '123-1',
                betTime: '2021-01-01 00:00:00',
            )
            ->willReturn(new Report);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $mockWalletReport,
            credentials: $stubCredentials
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockReport_makeSlotReport()
    {
        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: $request->transferId,
                gameCode: '1',
                betTime: '2021-01-01 00:00:00',
            )
            ->willReturn(new Report);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $mockWalletReport,
            credentials: $stubCredentials
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWallet_wagerAndPayout()
    {
        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->expects($this->once())
            ->method('wagerAndPayout')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->uid,
                currency: 'IDR',
                wagerTransactionID: "wagerpayout-{$request->transferId}",
                wagerAmount: 200,
                payoutTransactionID: "wagerpayout-{$request->transferId}",
                payoutAmount: 300,
                report: new Report,
            )
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport,
            credentials: $stubCredentials
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletWagerAndPayoutFail_walletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wagerAndPayout')
            ->willReturn(['status_code' => 123456789]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWallet_expected()
    {
        $expected = 1100.00;

        $request = (object) [
            'action' => 8,
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'gType' => 0,
            'mType' => 1,
            'bet' => -200,
            'win' => 300,
            'historyId' => 'testHistoryID'
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->betAndSettle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_bet_mockRepository_getPlayerByPlayID()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            walletReport: $stubReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubRepositoryNullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_getTransactionByTrxID()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->transferId)
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            walletReport: $stubReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubRepositoryWithTransaction_transactionAlreadyExistException()
    {
        $this->expectException(TransactionAlreadyExistException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => '123456']);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_mockCredentials_getCredentialsByCurrency()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(JdbCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubReport,
            credentials: $mockCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_balance()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->uid
            )
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            walletReport: $stubReport,
            credentials: $stubCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWalletBalanceReturnError_providerWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 321654987]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWalletNoBalance_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 0.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockResponse_createBetTransaction()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockRepository->expects($this->once())
            ->method('createBetTransaction')
            ->with(
                transactionID: $request->transferId,
                betAmount: $request->amount,
                betTime: '2021-01-01 00:00:00'
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            walletReport: $stubReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockReport_makeArcadeReport()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: $request->transferId,
                gameCode: "{$request->gType}-{$request->mType}",
                betTime: '2021-01-01 00:00:00'
            )
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $mockReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockReport_makeSlotReport()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 0,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: $request->transferId,
                gameCode: $request->mType,
                betTime: '2021-01-01 00:00:00'
            )
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $mockReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_wager()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->uid,
                currency: $request->currency,
                transactionID: "wager-{$request->transferId}",
                amount: $request->amount,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            walletReport: $stubReport,
            credentials: $stubCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWalletWagerReturnError_providerWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn(['status_code' => 894135684]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWallet_expected()
    {
        $expected = 900.00;

        $request = (object) [
            'ts' => 1609430400000,
            'transferId' => 123456,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'gType' => 9,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            walletReport: $stubReport
        );
        $response = $service->bet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_cancelBet_mockRepository_getPlayerByPlayID()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet
        );
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_stubRepositoryNullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_mockRepository_getTransactionByTrxID()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->refTransferIds[0])
            ->willReturn((object) ['updated_at' => null]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet
        );
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_stubRepositoryNullTransaction_providerTransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_mockCredentials_getCredentialsByCurrency()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockCredentials = $this->createMock(JdbCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_mockWallet_balance()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => '2021-01-01 00:00:00']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
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
                'credit' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_stubWalletBalance_providerWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => '2021-01-01 00:00:00']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 8754123]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_stubResponseBalance_expected()
    {
        $expected = 1100.00;

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => '2021-01-01 00:00:00']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $response = $service->cancelBet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_cancelBet_mockRepository_cancelBetTransaction()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockRepository->expects($this->once())
            ->method('cancelBetTransaction')
            ->with(
                transactionID: '123456',
                cancelTime: '2021-01-01 00:00:00'
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet
        );
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_mockWallet_cancel()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('cancel')
            ->with(
                credentials: $stubProviderCredentials,
                transactionID: "cancel-123456",
                amount: 100.00,
                transactionIDToCancel: "wager-123456"
            )
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_stubWalletCancel_providerWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn(['status_code' => 515462]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_stubResponseCancel_expected()
    {
        $expected = 1100.00;

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456]
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'credit_after' => 1100.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $response = $service->cancelBet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_settle_mockRepository_getPlayerByPlayID()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            walletReport: $stubReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubRepositoryNullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_getTransactionByTrxID()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->refTransferIds[0])
            ->willReturn((object) ['updated_at' => null]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            walletReport: $stubReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubRepositoryNullReturn_providerTransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_mockCredentials_getCredentialsByCurrency()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockCredentials = $this->createMock(JdbCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubReport,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_balance()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => '2024-01-01 00:00:00']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
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
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubWalletBalanceFail_providerWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => '2021-01-01 00:00:00']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 98765321]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubResponseTransactionAlreadySettled_expected()
    {
        $expected = 1000.00;

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => '2021-01-01 00:00:00']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet
        );
        $response = $service->settle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_settle_mockRepository_settleBetTransaction()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $mockRepository = $this->createMock(JdbRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockRepository->expects($this->once())
            ->method('settleBetTransaction')
            ->with(
                transactionID: $request->refTransferIds[0],
                historyID: $request->historyId,
                winAmount: $request->amount,
                settleTime: '2021-01-01 00:00:00'
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            walletReport: $stubReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockReport_makeArcadeReport()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: $request->refTransferIds[0],
                gameCode: "{$request->gType}-{$request->mType}",
                betTime: '2021-01-01 00:00:00'
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
            walletReport: $mockReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockReport_makeSlotReport()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 0,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: $request->refTransferIds[0],
                gameCode: $request->mType,
                betTime: '2021-01-01 00:00:00'
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
            walletReport: $mockReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_payout()
    {
        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(JdbCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: "payout-{$request->refTransferIds[0]}",
                amount: $request->amount,
                report: new Report,
            )
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubReport,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubWalletPayoutFail_providerWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn(['status_code' => 9871236]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubReport,
            wallet: $stubWallet
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubResponse_expected()
    {
        $expected = 1000.00;

        $request = (object) [
            'ts' => 1609430400000,
            'uid' => 'testPlayID',
            'currency' => 'IDR',
            'amount' => 100,
            'refTransferIds' => [123456],
            'historyId' => 'testHistoryID',
            'gType' => 7,
            'mType' => 123
        ];

        $stubRepository = $this->createMock(JdbRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            walletReport: $stubReport,
            wallet: $stubWallet
        );
        $response = $service->settle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}