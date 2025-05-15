<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use App\GameProviders\V2\Bes\BesRepository;
use App\GameProviders\V2\Bes\BesCredentials;
use App\GameProviders\V2\Bes\BesProviderService;
use App\GameProviders\V2\Bes\Exceptions\WalletException;
use App\GameProviders\V2\Bes\Exceptions\PlayerNotFoundException;
use App\GameProviders\V2\Bes\Exceptions\InsufficientFundException;
use App\GameProviders\V2\Bes\Exceptions\TransactionDoesntExistsException;
use App\GameProviders\V2\Bes\Exceptions\TransactionAlreadyExistsException;

class BesProviderServiceTest extends TestCase
{
    public function makeService($repository = null, $credentials = null, $wallet = null, $walletReport = null)
    {
        $repository ??= $this->createMock(BesRepository::class);
        $credentials ??= $this->createMock(BesCredentials::class);
        $wallet ??= $this->createMock(IWallet::class);
        $walletReport ??= $this->createMock(WalletReport::class);

        return new BesProviderService(
            repository: $repository,
            credentials: $credentials,
            wallet: $wallet,
            walletReport: $walletReport
        );
    }

    public function test_getBalance_DBPlayerNotFound_PlayerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'uid' => 'test-uid',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService($stubRepository);
        $service->getBalance($request);
    }

    public function test_getBalance_WalletStatusCodeNot2100_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'uid' => 'test-uid',
            'currency' => 'IDR'
        ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999
            ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object)['test']);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->getBalance($request);
    }

    public function test_settleBet_playerDetailsEmpty_PlayerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'uid' => 'test-uid'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settleBet($request);
    }

    public function test_settleBet_transactionDetailsEmpty_TransactionDoesntExistsException()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request([
            'uid' => 'test-uid',
            'roundId' => 'test'
        ]);

        $stubRepository = $this->createMock(BesRepository::class);
        $stubRepository->method('getTransactionByTransactionID')
            ->willReturn(collect());

        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(collect(['test']));

        $service = $this->makeService(repository: $stubRepository);
        $service->settleBet($request);
    }

    public function test_settleBet_walletBalanceStatusCodeNotSuccess_WalletException()
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
        $stubRepository->method('getTransactionByTransactionID')
            ->willReturn(null);

        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(
                (object)[
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

        $service->settleBet($request);
    }

    public function test_settleBet_walletBalanceNotEnough_WalletException()
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
        $stubRepository->method('getTransactionByTransactionID')
            ->willReturn(null);

        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(
                (object)[
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

        $service->settleBet($request);
    }

    public function test_settleBet_walletWagerAndPayoutStatusCodeNotSuccess_WalletException()
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
        $stubRepository->method('getTransactionByTransactionID')
            ->willReturn(null);

        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(
                (object)[
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

        $service->settleBet($request);
    }
}
