<?php

use Tests\TestCase;
use Providers\Hcg\HcgApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Hcg\HcgService;
use Providers\Hcg\HcgRepository;
use Providers\Hcg\HcgCredentials;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Hcg\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Hcg\Exceptions\WalletErrorException;
use Providers\Hcg\Exceptions\CannotCancelException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Hcg\Exceptions\InsufficientFundException;
use Providers\Hcg\Exceptions\TransactionAlreadyExistException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Hcg\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;

class HcgServiceTest extends TestCase
{
    private function makeService(
        HcgRepository $repository = null,
        HcgCredentials $credentials = null,
        HcgApi $api = null,
        IWallet $wallet = null,
        WalletReport $report = null
    ): HcgService {
        $repository ??= $this->createStub(HcgRepository::class);
        $credentials ??= $this->createStub(HcgCredentials::class);
        $api ??= $this->createStub(HcgApi::class);
        $wallet ??= $this->createStub(IWallet::class);
        $report ??= $this->createStub(WalletReport::class);

        return new HcgService(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            wallet: $wallet,
            report: $report
        );
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

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $service = $this->makeService(repository: $mockRepository);
        $service->getVisualUrl(request: $request);
    }

    public function test_getVisualUrl_stubRepositoryNullPlayer_playerNotFoundException()
    {
        $this->expectException(CasinoPlayerNotFoundException::class);

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

    public function test_getBalance_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) ['play_id' => 'testPlayID', 'currency' => 'IDR']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $service = $this->makeService(repository: $mockRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubRepositoryNullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID', 'currency' => 'IDR']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

        $mockCredentials = $this->createMock(HcgCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockWallet_balance()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID', 'currency' => 'IDR']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: $request->uid)
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_emptyWallet_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID', 'currency' => 'IDR']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 'invalid'
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWallet_expected()
    {
        $request = new Request([
            'action' => 1,
            'uid' => 'testPlayID',
            'sign' => 'testSign'
        ]);

        $expected = 1000.0;

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID', 'currency' => 'IDR']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => $expected
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $response = $service->getBalance(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_betAndSettle_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $providerCredentials->method('getTransactionIDPrefix')
            ->willReturn('0');

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) ['currency' => 'IDR']);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRepositoryNullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $providerCredentials->method('getTransactionIDPrefix')
            ->willReturn('0');

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: "0-{$request->orderNo}")
            ->willReturn(null);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRepositoryTransactionExist_transactionAlreadyExistException()
    {
        $this->expectException(TransactionAlreadyExistException::class);

        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $service = $this->makeService(repository: $stubRepository);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $mockCredentials = $this->createMock(HcgCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWallet_balance()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: $request->uid)
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $mockWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            report: $stubReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletBalanceError_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 'invalid'
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletInsufficientFunds_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockRepository_createSettleTransaction()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 123456789,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $providerCredentials->method('getTransactionIDPrefix')
            ->willReturn('0');

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->expects($this->once())
            ->method('createSettleTransaction')
            ->with(
                transactionID: "0-{$request->orderNo}",
                betAmount: 1000,
                winAmount: 3000
            );

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockReport_makeSlotReport()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 1723618062,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $providerCredentials->method('getTransactionIDPrefix')
            ->willReturn('0');

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000.0
            ]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: "0-{$request->orderNo}",
                gameCode: $request->gameCode,
                betTime: '2024-08-14 14:47:42'
            )
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $mockReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWallet_wagerAndPayout()
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 1723618062,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $providerCredentials->method('getTransactionIDPrefix')
            ->willReturn('0');

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $mockWallet->expects($this->once())
            ->method('wagerAndPayout')
            ->with(
                credentials: $providerCredentials,
                playID: $request->uid,
                currency: 'IDR',
                wagerTransactionID: "wagerpayout-0-{$request->orderNo}",
                wagerAmount: 1000,
                payoutTransactionID: "wagerpayout-0-{$request->orderNo}",
                payoutAmount: 3000,
                report: new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            report: $stubReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletWagerAndPayoutError_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 1723618062,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 'invalid'
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );
        $service->betAndSettle(request: $request);
    }

    #[DataProvider('currencyConversionExpectedData')]
    public function test_betAndSettle_stubWallet_expectedData($currency, $conversionRatio, $expected)
    {
        $request = new Request([
            'action' => 2,
            'uid' => 'testPlayID',
            'timestamp' => 1723618062,
            'orderNo' => 'testTransactionID',
            'gameCode' => '123',
            'bet' => 1,
            'win' => 3,
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn($conversionRatio);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => $currency]);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );
        $result = $service->betAndSettle(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }

    public static function currencyConversionExpectedData()
    {
        return [
            ['IDR', 1000, 4.0],
            ['PHP', 1, 4000.0],
        ];
    }

    public function test_cancelBetAndSettle_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'orderNo' => 'testTransactionID',
            'sign' => 'testSign'
        ]);

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->uid)
            ->willReturn((object) ['currency' => 'IDR']);

        $service = $this->makeService(repository: $mockRepository);
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_stubRepositoryNullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'orderNo' => 'testTransactionID',
            'sign' => 'testSign'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'orderNo' => 'testTransactionID',
            'sign' => 'testSign'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $mockCredentials = $this->createMock(HcgCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials);
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'orderNo' => 'testTransactionID',
            'sign' => 'testSign'
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getTransactionIDPrefix')
            ->willReturn('0');

        $mockRepository = $this->createMock(HcgRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: "0-{$request->orderNo}");

        $service = $this->makeService(repository: $mockRepository, credentials: $stubCredentials);
        $service->cancelBetAndSettle(request: $request);
    }

    public function test_cancelBetAndSettle_stubRepositoryTransactionExist_cannotCancelException()
    {
        $this->expectException(CannotCancelException::class);

        $request = new Request([
            'action' => 3,
            'uid' => 'testPlayID',
            'orderNo' => 'testTransactionID',
            'sign' => 'testSign'
        ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $service = $this->makeService(repository: $stubRepository);
        $service->cancelBetAndSettle(request: $request);
    }
}