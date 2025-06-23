<?php

use Carbon\Carbon;
use Providers\Aix\Credentials\Staging;
use Providers\Aix\DTO\AixPlayerDTO;
use Providers\Aix\DTO\AixTransactionDTO;
use Tests\TestCase;
use Providers\Aix\AixApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Aix\AixService;
use Providers\Aix\AixRepository;
use Providers\Aix\AixCredentials;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Aix\Contracts\ICredentials;
use App\Exceptions\Casino\WalletErrorException;
use Providers\Aix\DTO\AixRequestDTO;
use Providers\Aix\Exceptions\PlayerNotFoundException;
use Providers\Aix\Exceptions\InsufficientFundException;
use Providers\Aix\Exceptions\InvalidSecretKeyException;
use Providers\Aix\Exceptions\TransactionIsNotSettledException;
use Providers\Aix\Exceptions\TransactionAlreadyExistsException;
use Providers\Aix\Exceptions\TransactionAlreadySettledException;
use Providers\Aix\Exceptions\ProviderTransactionNotFoundException;
use Providers\Aix\Exceptions\WalletErrorException as WalletException;
use Providers\Aix\Exceptions\TransactionAlreadySettledException as DuplicateBonusException;

use function PHPUnit\Framework\exactly;

class AixServiceTest extends TestCase
{
    public function makeService(
        $repository = null,
        $credentials = null,
        $wallet = null,
        $api = null,
        $walletReport = null
    ): AixService {

        $repository ??= $this->createMock(AixRepository::class);
        $credentials ??= $this->createMock(AixCredentials::class);
        $wallet ??= $this->createMock(IWallet::class);
        $api ??= $this->createMock(AixApi::class);
        $walletReport ??= $this->createMock(WalletReport::class);

        return new AixService(
            repository: $repository,
            credentials: $credentials,
            wallet: $wallet,
            api: $api,
            walletReport: $walletReport
        );
    }

    public function test_getLaunchUrl_mockWallet_balance()
    {
        $credentials = new Staging;

        $requestDTO = new CasinoRequestDTO(
            playID: 'test-play-idu001',
            username: 'test-username',
            currency: 'IDR',
        );

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                $credentials,
                'test-play-idu001'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $stubCredentials = $this->createMock(AixCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            wallet: $mockWallet,
            credentials: $stubCredentials
        );

        $service->getLaunchUrl($requestDTO);
    }

    public function test_balance_mockWallet_balance()
    {
        $credentials = new Staging;

        $requestDTO = new AixRequestDTO(
            secretKey: $credentials->getSecretKey(),
            playID: 'test-play-idu001',
        );

        $playerDTO = new AixPlayerDTO(
            playID: 'test-play-idu001',
            username: 'test-username',
            currency: 'IDR',
        );

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                $credentials,
                'test-play-idu001'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $stubRepository = $this->createMock(AixRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubCredentials = $this->createMock(AixCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            wallet: $mockWallet,
            repository: $stubRepository,
            credentials: $stubCredentials
        );

        $service->balance($requestDTO);
    }

    public function test_wager_mockWalletReport_makeSlotReport()
    {
        $credentials = new Staging;

        $requestDTO = new AixRequestDTO(
            secretKey: $credentials->getSecretKey(),
            roundID: 'test-round',
            playID: 'test-play-idu001',
            gameID: 'test-game-id',
            amount: 100,
            dateTime: '2026-01-01 00:00:00',
        );

        $playerDTO = new AixPlayerDTO(
            playID: 'test-play-idu001',
            username: 'test-username',
            currency: 'IDR',
        );

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                'test-round',
                'test-game-id',
                '2026-01-01 00:00:00'
            )
            ->willReturn(new Report);

        $stubRepository = $this->createMock(AixRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);


        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $stubCredentials = $this->createMock(AixCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            walletReport: $mockWalletReport,
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->wager($requestDTO);
    }

    public function test_wager_mockWallet_wager()
    {
        $credentials = new Staging;

        $requestDTO = new AixRequestDTO(
            secretKey: $credentials->getSecretKey(),
            roundID: 'test-round',
            playID: 'test-play-idu001',
            gameID: 'test-game-id',
            amount: 100,
            dateTime: '2026-01-01 00:00:00',
        );

        $playerDTO = new AixPlayerDTO(
            playID: 'test-play-idu001',
            username: 'test-username',
            currency: 'IDR',
        );

        $walletReport = new Report;

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                $credentials,
                'test-play-idu001',
                'IDR',
                'wager-test-round',
                100,
                $walletReport
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn($walletReport);

        $stubRepository = $this->createMock(AixRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);

        $stubCredentials = $this->createMock(AixCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            wallet: $mockWallet,
            walletReport: $stubWalletReport,
            repository: $stubRepository,
            credentials: $stubCredentials
        );

        $service->wager($requestDTO);
    }

    public function test_payout_mockWalletReport_makeSlotReport()
    {
        $credentials = new Staging;

        $requestDTO = new AixRequestDTO(
            secretKey: $credentials->getSecretKey(),
            roundID: 'test-round',
            playID: 'test-play-idu001',
            gameID: 'test-game-id',
            amount: 1000,
            dateTime: '2026-01-01 00:00:00',
        );

        $playerDTO = new AixPlayerDTO(
            playID: 'test-play-idu001',
            username: 'test-username',
            currency: 'IDR',
        );

        $wagerTransaction = new AixTransactionDTO(
            roundID: 'test-round',
            playID: 'test-play-idu001',
            username: 'test-username',
            webID: 1,
            currency: 'IDR',
            gameID: 'test-game-id',
            betAmount: 100
        );

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                'test-round',
                'test-game-id',
                '2026-01-01 00:00:00'
            )
            ->willReturn(new Report);

        $stubRepository = $this->createMock(AixRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls($wagerTransaction, null);


        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $stubCredentials = $this->createMock(AixCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            walletReport: $mockWalletReport,
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->payout($requestDTO);
    }

    public function test_payout_mockWallet_payout()
    {
        $credentials = new Staging;

        $requestDTO = new AixRequestDTO(
            secretKey: $credentials->getSecretKey(),
            roundID: 'test-round',
            playID: 'test-play-idu001',
            gameID: 'test-game-id',
            amount: 1000,
            dateTime: '2026-01-01 00:00:00',
        );

        $playerDTO = new AixPlayerDTO(
            playID: 'test-play-idu001',
            username: 'test-username',
            currency: 'IDR',
        );

        $wagerTransaction = new AixTransactionDTO(
            roundID: 'test-round',
            playID: 'test-play-idu001',
            username: 'test-username',
            webID: 1,
            currency: 'IDR',
            gameID: 'test-game-id',
            betAmount: 100
        );

        $walletReport = new Report;

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                $credentials,
                'test-play-idu001',
                'IDR',
                'payout-test-round',
                1000,
                $walletReport
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn($walletReport);

        $stubRepository = $this->createMock(AixRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls($wagerTransaction, null);

        $stubCredentials = $this->createMock(AixCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            wallet: $mockWallet,
            walletReport: $stubWalletReport,
            repository: $stubRepository,
            credentials: $stubCredentials
        );

        $service->payout($requestDTO);
    }

    public function test_bonus_mockWalletReport_makeSlotReport()
    {
        Carbon::setTestNow('2026-01-01 00:00:00');

        $credentials = new Staging;

        $requestDTO = new AixRequestDTO(
            secretKey: $credentials->getSecretKey(),
            roundID: 'test-round',
            playID: 'test-play-idu001',
            gameID: 'test-game-id',
            amount: 1000,
        );

        $playerDTO = new AixPlayerDTO(
            playID: 'test-play-idu001',
            username: 'test-username',
            currency: 'IDR',
        );

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeBonusReport')
            ->with(
                'test-round',
                'test-game-id',
                '2026-01-01 00:00:00'
            )
            ->willReturn(new Report);

        $stubRepository = $this->createMock(AixRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $stubCredentials = $this->createMock(AixCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            walletReport: $mockWalletReport,
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->bonus($requestDTO);
    }

    public function test_bonus_mockWallet_bonus()
    {
        $credentials = new Staging;

        $requestDTO = new AixRequestDTO(
            secretKey: $credentials->getSecretKey(),
            roundID: 'test-round',
            playID: 'test-play-idu001',
            gameID: 'test-game-id',
            amount: 1000,
            dateTime: '2026-01-01 00:00:00',
        );

        $playerDTO = new AixPlayerDTO(
            playID: 'test-play-idu001',
            username: 'test-username',
            currency: 'IDR',
        );

        $report = new Report;

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('bonus')
            ->with(
                $credentials,
                'test-play-idu001',
                'IDR',
                'bonus-test-round',
                1000,
                $report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeBonusReport')
            ->willReturn($report);

        $stubRepository = $this->createMock(AixRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);

        $stubCredentials = $this->createMock(AixCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            wallet: $mockWallet,
            walletReport: $stubWalletReport,
            repository: $stubRepository,
            credentials: $stubCredentials
        );

        $service->bonus($requestDTO);
    }
}
