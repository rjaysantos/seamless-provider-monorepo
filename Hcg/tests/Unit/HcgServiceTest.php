<?php

use Tests\TestCase;
use Providers\Hcg\HcgApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Hcg\HcgService;
use Providers\Hcg\HcgRepository;
use Providers\Hcg\HcgCredentials;
use Providers\Hcg\DTO\HcgRequestDTO;
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
use Providers\Hcg\Credentials\HcgStagingIDR;
use Providers\Hcg\DTO\HcgPlayerDTO;

class HcgServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $wallet = null,
        $report = null
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

    public function test_getBalance_mockWallet_balance()
    {
        $credentials = new HcgStagingIDR();

        $requestDTO = new HcgRequestDTO(
            playID: 'testPlayIDu001'
        );

        $playerDTO = new HcgPlayerDTO(
            playID: 'testPlayIDu001',
            username: 'testUsername',
            currency: 'IDR'
        );

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $credentials, 
                playID:'testPlayIDu001'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            wallet: $mockWallet,
            repository: $stubRepository,
            credentials: $stubCredentials
        );

        $service->getBalance(requestDTO: $requestDTO);
    }

    public function test_betAndSettle_mockWallet_balance()
    {
        $credentials = new HcgStagingIDR();

        $requestDTO = new HcgRequestDTO(
            playID: 'testPlayIDu001',
            dateTime: 1723618062,
            roundID: 'testRoundID',
            gameID: '123',
            amount: 1,
            winAmount: 3
        );

        $playerDTO = new HcgPlayerDTO(
            playID: 'testPlayIDu001',
            username: 'testUsername',
            currency: 'IDR'
        );

        $walletReport = new Report;

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $credentials, 
                playerDTO: 'testPlayIDu001'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $mockWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000.0
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn($walletReport);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            report: $stubWalletReport
        );
        $service->betAndSettle(requestDTO: $requestDTO);
    }

    public function test_betAndSettle_mockWallet_wagerAndPayout()
    {
        $credentials = new HcgStagingIDR();

        $requestDTO = new HcgRequestDTO(
            playID: 'testPlayIDu001',
            dateTime: 1723618062,
            roundID: 'testRoundID',
            gameID: '123',
            amount: 1,
            winAmount: 3
        );

        $playerDTO = new HcgPlayerDTO(
            playID: 'testPlayIDu001',
            username: 'testUsername',
            currency: 'IDR'
        );

        $walletReport = new Report;

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('wagerAndPayout')
            ->with(
                credentials: $credentials,
                playID: 'testPlayIDu001',
                currency: 'IDR',
                wagerTransactionID: "wagerpayout-{$requestDTO->roundID}",
                wagerAmount: 1000,
                payoutTransactionID: "wagerpayout-{$requestDTO->roundID}",
                payoutAmount: 3000,
                report: $walletReport
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 4000.0
            ]);

        $mockWallet->expects($this->once())
            ->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn($walletReport);

        $stubRepository = $this->createMock(HcgRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);

        $stubCredentials = $this->createMock(HcgCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            report: $stubWalletReport
        );
        $service->betAndSettle(requestDTO: $requestDTO);
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