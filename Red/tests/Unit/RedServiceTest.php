<?php

use Carbon\Carbon;
use Tests\TestCase;
use Providers\Red\RedApi;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Red\RedService;
use Providers\Red\RedRepository;
use Providers\Red\RedCredentials;
use Providers\Red\DTO\RedPlayerDTO;
use Providers\Red\DTO\RedRequestDTO;
use Wallet\V1\ProvSys\Transfer\Report;
use Providers\Red\DTO\RedTransactionDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Red\Contracts\ICredentials;

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

    public function test_getLaunchUrl_mockWallet_balance()
    {
        $requestDTO = new CasinoRequestDTO(
            playID: 'testPlayIDu001',
            username: 'testUsername',
            currency: 'IDR',
        );

        $providerCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayIDu001'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $stubApi = $this->createMock(RedApi::class);
        $stubApi->method('authenticate')
            ->willReturn((object)[
                'userID' => 123,
                'launchUrl' => 'testLaunchUrl'
            ]);

        $service = $this->makeService(wallet: $mockWallet, credentials: $stubCredentials, api: $stubApi);
        $service->getLaunchUrl($requestDTO);
    }

    public function test_balance_mockWallet_balance()
    {
        $requestDTO = new RedRequestDTO(
            secretKey: 'testSecretKey',
            providerUserID: 123
        );

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(new RedPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getSecretKey')->willReturn('testSecretKey');

        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayIDu001'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
        $service->balance($requestDTO);
    }

    public function test_wager_mockWallet_balance()
    {
        $requestDTO = new RedRequestDTO(
            secretKey: 'testSecretKey',
            providerUserID: 123,
            gameID: 456,
            roundID: 'testTransactionID',
            amount: 100.00,
            dateTime: '2025-01-01 00:00:00'
        );

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(new RedPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getSecretKey')->willReturn('testSecretKey');

        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayIDu001'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 900
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->wager($requestDTO);
    }

    public function test_wager_mockWalletReport_makeSlotReport()
    {
        $requestDTO = new RedRequestDTO(
            secretKey: 'testSecretKey',
            providerUserID: 123,
            gameID: 456,
            roundID: 'testTransactionID',
            amount: 100.00,
            dateTime: '2025-01-01 00:00:00'
        );

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(new RedPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getSecretKey')->willReturn('testSecretKey');

        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: 'testTransactionID',
                gameCode: 456,
                betTime: '2025-01-01 08:00:00'
            )
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 900
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $mockWalletReport
        );
        $service->wager($requestDTO);
    }

    public function test_wager_mockWallet_wager()
    {
        $requestDTO = new RedRequestDTO(
            secretKey: 'testSecretKey',
            providerUserID: 123,
            gameID: 456,
            roundID: 'testTransactionID',
            amount: 100.00,
            dateTime: '2025-01-01 00:00:00'
        );

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(new RedPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getSecretKey')->willReturn('testSecretKey');

        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayIDu001',
                currency: 'IDR',
                transactionID: 'wager-testTransactionID',
                amount: 100.00,
                report: new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 900
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->wager($requestDTO);
    }

    public function test_payout_mockWalletReport_makeSlotReport()
    {
        $requestDTO = new RedRequestDTO(
            secretKey: 'testSecretKey',
            providerUserID: 123,
            gameID: 456,
            roundID: 'testTransactionID',
            amount: 300.00,
            dateTime: '2025-01-01 00:00:00'
        );

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(new RedPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls(new RedTransactionDTO(
                roundID: 'testTransactionID',
                gameID: 456,
                playID: 'testPlayIDu001',
                currency: 'IDR',
            ), null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getSecretKey')->willReturn('testSecretKey');

        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: 'testTransactionID',
                gameCode: 456,
                betTime: '2025-01-01 08:00:00'
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1200.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $mockWalletReport
        );
        $service->payout($requestDTO);
    }

    public function test_payout_mockWallet_payout()
    {
        $requestDTO = new RedRequestDTO(
            secretKey: 'testSecretKey',
            providerUserID: 123,
            gameID: 456,
            roundID: 'testTransactionID',
            amount: 300.00,
            dateTime: '2025-01-01 00:00:00'
        );

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(new RedPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls(new RedTransactionDTO(
                roundID: 'testTransactionID',
                gameID: 456,
                playID: 'testPlayIDu001',
                currency: 'IDR',
            ), null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getSecretKey')->willReturn('testSecretKey');

        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayIDu001',
                currency: 'IDR',
                transactionID: 'payout-testTransactionID',
                amount: 300.00,
                report: new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1200.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->payout($requestDTO);
    }

    public function test_bonus_mockWalletReport_makeBonusReport()
    {
        Carbon::setTestNow('2025-01-01 00:00:00');
        $requestDTO = new RedRequestDTO(
            secretKey: 'testSecretKey',
            providerUserID: 123,
            gameID: 456,
            roundID: 'testTransactionID',
            amount: 200.00,
        );

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(new RedPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getSecretKey')->willReturn('testSecretKey');

        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeBonusReport')
            ->with(
                transactionID: 'testTransactionID',
                gameCode: 456,
                betTime: '2025-01-01 00:00:00'
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1200.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $mockWalletReport
        );
        $service->bonus($requestDTO);
    }

    public function test_bonus_mockWallet_bonus()
    {
        $requestDTO = new RedRequestDTO(
            secretKey: 'testSecretKey',
            providerUserID: 123,
            gameID: 456,
            roundID: 'testTransactionID',
            amount: 200.00,
        );

        $stubRepository = $this->createMock(RedRepository::class);
        $stubRepository->method('getPlayerByUserIDProvider')
            ->willReturn(new RedPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getSecretKey')->willReturn('testSecretKey');

        $stubCredentials = $this->createMock(RedCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('bonus')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayIDu001',
                currency: 'IDR',
                transactionID: 'bonus-testTransactionID',
                amount: 200.00,
                report: new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1200.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->bonus($requestDTO);
    }
}
