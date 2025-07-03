<?php

use Tests\TestCase;
use Providers\Gs5\Gs5Api;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Gs5\Gs5Service;
use Providers\Gs5\Gs5Repository;
use Providers\Gs5\Gs5Credentials;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Gs5\Contracts\ICredentials;
use Providers\Gs5\DTO\Gs5PlayerDTO;
use Providers\Gs5\DTO\GS5RequestDTO;
use Providers\Gs5\DTO\Gs5TransactionDTO;
use Wallet\V1\ProvSys\Transfer\Report;

class Gs5ServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $wallet = null,
        $report = null,
        $api = null
    ): Gs5Service {
        $repository ??= $this->createStub(Gs5Repository::class);
        $credentials ??= $this->createStub(Gs5Credentials::class);
        $api ??= $this->createStub(Gs5Api::class);
        $wallet ??= $this->createStub(IWallet::class);
        $report ??= $this->createStub(WalletReport::class);

        return new Gs5Service(
            repository: $repository,
            credentials: $credentials,
            wallet: $wallet,
            report: $report,
            api: $api
        );
    }

    public function test_getBalance_mockWallet_balance()
    {
        $requestDTO = GS5RequestDTO::tokenRequest(
            new Request([
                'access_token' => 'test-token'
            ])
        );

        $credentials = $this->createMock(ICredentials::class);

        $playerData = Gs5PlayerDTO::fromDB((object)[
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'testCurrency',
            'token' => 'testToken',
        ]);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with($credentials, 'testPlayID')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000,
            ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn($playerData);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(wallet: $mockWallet, repository: $stubRepository, credentials: $stubCredentials);
        $service->getBalance($requestDTO);
    }

    public function test_authenticate_mockWallet_balance()
    {
        $requestDTO = GS5RequestDTO::tokenRequest(
            new Request([
                'access_token' => 'test-token'
            ])
        );

        $credentials = $this->createMock(ICredentials::class);

        $playerData = Gs5PlayerDTO::fromDB((object)[
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'testCurrency',
            'token' => 'testToken',
        ]);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with($credentials, 'testPlayID')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000,
            ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn($playerData);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(wallet: $mockWallet, repository: $stubRepository, credentials: $stubCredentials);
        $service->authenticate($requestDTO);
    }

    public function test_wager_mockWallet_balance()
    {
        $requestDTO = GS5RequestDTO::fromBetRequest(
            new Request([
                'access_token' => 'test-token',
                'txn_id' => 'test-txnid',
                'total_bet' => 1000,
                'game_id' => 'test-gameid',
                'ts' => 1704038400
            ])
        );

        $credentials = $this->createMock(ICredentials::class);

        $playerData = Gs5PlayerDTO::fromDB((object)[
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'testCurrency',
            'token' => 'testToken',
        ]);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with($credentials, 'testPlayID')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000,
            ]);

        $mockWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000,
            ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn($playerData);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(wallet: $mockWallet, repository: $stubRepository, credentials: $stubCredentials);
        $service->wager($requestDTO);
    }

    public function test_wager_mockWalletReport_makeSlotReport()
    {
        $requestDTO = GS5RequestDTO::fromBetRequest(
            new Request([
                'access_token' => 'test-token',
                'txn_id' => 'test-txnid',
                'total_bet' => 1000,
                'game_id' => 'test-gameid',
                'ts' => 1704038400
            ])
        );

        $credentials = $this->createMock(ICredentials::class);

        $playerData = Gs5PlayerDTO::fromDB((object)[
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'testCurrency',
            'token' => 'testToken',
        ]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                'test-txnid',
                'test-gameid',
                '2024-01-01 00:00:00'
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000,
            ]);

        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000,
            ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn($playerData);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            report: $mockReport,
            wallet: $stubWallet,
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->wager($requestDTO);
    }

    public function test_wager_mockWallet_wager()
    {
        $requestDTO = GS5RequestDTO::fromBetRequest(
            new Request([
                'access_token' => 'test-token',
                'txn_id' => 'test-txnid',
                'total_bet' => 1000,
                'game_id' => 'test-gameid',
                'ts' => 1704038400
            ])
        );

        $credentials = $this->createMock(ICredentials::class);

        $playerData = Gs5PlayerDTO::fromDB((object)[
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'testCurrency',
            'token' => 'testToken',
        ]);

        $report = $this->createMock(Report::class);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                $credentials,
                'testPlayID',
                'testCurrency',
                'wager-test-txnid',
                10,
                $report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000,
            ]);

        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000,
            ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn($playerData);

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(null);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $service = $this->makeService(
            wallet: $mockWallet,
            repository: $stubRepository,
            credentials: $stubCredentials,
            report: $stubReport
        );

        $service->wager($requestDTO);
    }

    public function test_payout_mockWalletReport_makeSlotReport()
    {
        $requestDTO = GS5RequestDTO::fromResultRequest(
            new Request([
                'access_token' => 'test-token',
                'txn_id' => 'test-txnid',
                'total_win' => 1000,
                'game_id' => 'test-gameid',
                'ts' => 1704038400
            ])
        );

        $credentials = $this->createMock(ICredentials::class);

        $playerData = Gs5PlayerDTO::fromDB((object)[
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'testCurrency',
            'token' => 'testToken',
        ]);

        $wagerTransactionDTO = Gs5TransactionDTO::fromDB((object)[
            'ext_id' => 'wager-testRoundId',
            'round_id' => 'testRoundId',
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'web_id' => 1,
            'currency' => 'testCurrency',
            'game_code' => 'testGameID',
            'bet_amount' => 100,
            'bet_valid' => 100,
            'bet_winlose' => 0,
            'created_at' => '2024-01-01 00:00:00',
        ]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                'testRoundId',
                'testGameID',
                '2024-01-01 00:00:00'
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000,
            ]);

        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000,
            ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn($playerData);

        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls($wagerTransactionDTO, null);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            report: $mockReport,
            wallet: $stubWallet,
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->payout($requestDTO);
    }

    public function test_payout_mockWallet_payout()
    {
        $requestDTO = GS5RequestDTO::fromResultRequest(
            new Request([
                'access_token' => 'test-token',
                'txn_id' => 'test-txnid',
                'total_win' => 1000,
                'game_id' => 'test-gameid',
                'ts' => 1704038400
            ])
        );

        $credentials = $this->createMock(ICredentials::class);

        $playerData = Gs5PlayerDTO::fromDB((object)[
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'testCurrency',
            'token' => 'testToken',
        ]);

        $wagerTransactionDTO = Gs5TransactionDTO::fromDB((object)[
            'ext_id' => 'wager-testRoundId',
            'round_id' => 'testRoundId',
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'web_id' => 1,
            'currency' => 'testCurrency',
            'game_code' => 'testGameID',
            'bet_amount' => 100,
            'bet_valid' => 100,
            'bet_winlose' => 0,
            'created_at' => '2024-01-01 00:00:00',
        ]);

        $report = $this->createMock(Report::class);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                $credentials,
                'testPlayID',
                'testCurrency',
                'payout-test-txnid',
                10,
                $report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000,
            ]);

        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000,
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn($playerData);

        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls($wagerTransactionDTO, null);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            wallet: $mockWallet,
            report: $stubReport,
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->payout($requestDTO);
    }

    public function test_cancel_mockWallet_cancel()
    {
        $requestDTO = GS5RequestDTO::fromRefundRequest(
            new Request([
                'access_token' => 'test-token',
                'txn_id' => 'testRoundId',
            ])
        );

        $credentials = $this->createMock(ICredentials::class);

        $playerData = Gs5PlayerDTO::fromDB((object)[
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'testCurrency',
            'token' => 'testToken',
        ]);

        $wagerTransactionDTO = Gs5TransactionDTO::fromDB((object)[
            'ext_id' => 'wager-testRoundId',
            'round_id' => 'testRoundId',
            'play_id' => 'testPlayID',
            'username' => 'testUsername',
            'web_id' => 1,
            'currency' => 'testCurrency',
            'game_code' => 'testGameID',
            'bet_amount' => 100,
            'bet_valid' => 100,
            'bet_winlose' => 0,
            'created_at' => '2024-01-01 00:00:00',
        ]);


        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('cancel')
            ->with(
                $credentials,
                'cancel-testRoundId',
                100,
                'wager-testRoundId'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000,
            ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn($playerData);

        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls($wagerTransactionDTO, null);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(
            wallet: $mockWallet,
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->cancel($requestDTO);
    }
}
