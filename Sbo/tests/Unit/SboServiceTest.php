<?php

use Providers\Sbo\Exceptions\TransactionAlreadyVoidException;
use Providers\Sbo\SportsbookDetails\SboCancelSportsbookDetails;
use Providers\Sbo\SportsbookDetails\SboRunningSportsbookDetails;
use Tests\TestCase;
use Providers\Sbo\SboApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Sbo\SboService;
use Providers\Sbo\SboRepository;
use Providers\Sbo\SboCredentials;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Sbo\Contracts\ICredentials;
use Providers\Sbo\Exceptions\WalletException;
use Providers\Sbo\Exceptions\InvalidCompanyKeyException;
use Providers\Sbo\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use Providers\Sbo\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;
use Wallet\V1\ProvSys\Transfer\Report;

class SboServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $wallet = null,
        $walletReport = null
    ): SboService {
        $repository ??= $this->createStub(SboRepository::class);
        $credentials ??= $this->createStub(SboCredentials::class);
        $api ??= $this->createStub(SboApi::class);
        $wallet ??= $this->createStub(IWallet::class);
        $walletReport ??= $this->createStub(WalletReport::class);

        return new SboService($repository, $credentials, $api, $wallet, $walletReport);
    }

    public function test_cancel_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('testPlayerIDu027')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'settled',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->cancel($request);
    }

    public function test_cancel_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->cancel($request);
    }

    public function test_cancel_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $mockCredentials = $this->createMock(SboCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with('IDR')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'settled',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, wallet: $stubWallet);
        $service->cancel($request);
    }

    public function test_cancel_stubCredentialsInvalidKey_InvalidCompanyKeyException()
    {
        $this->expectException(InvalidCompanyKeyException::class);

        $request = new Request([
            'CompanyKey' => 'invalidCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->cancel($request);
    }

    public function test_cancel_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with($request->TransferCode)
            ->willReturn((object) [
                'flag' => 'settled',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->cancel($request);
    }

    public function test_cancel_stubRepositoryNullTransaction_ProviderTransactioNNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->cancel($request);
    }

    public function test_cancel_mockWallet_balance()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: 'testPlayerIDu027')
            ->willReturn([
                'credit' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
        $service->cancel($request);
    }

    public function test_cancel_stubWalletBalanceError_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 648213486]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->cancel($request);
    }

    public function test_cancel_stubRepositoryTransactionAlreadyVoid_TransactionAlreadyVoidException()
    {
        $this->expectException(TransactionAlreadyVoidException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['flag' => 'void']);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->cancel($request);
    }

    public function test_cancel_mockWalletReport_makeSportsbookReport()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);


        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'running',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'game_code' => 0,
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSportsbookReport')
            ->with(
                trxID: $request->TransferCode,
                betTime: '2024-01-01 00:00:00',
                sportsbookDetails: new SboRunningSportsbookDetails(gameCode: 0)
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $mockWalletReport
        );
        $service->cancel($request);
    }

    public function test_cancel_mockWallet_payout()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'running',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'game_code' => 0,
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayerIDu027',
                currency: 'IDR',
                transactionID: 'payout-1-testTransactionID',
                amount: 0.00,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $mockWallet->method('resettle')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->cancel($request);
    }

    public function test_cancel_stubWalletPayoutError_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'running',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'game_code' => 0,
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn(['status_code' => 46125.12]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->cancel($request);
    }

    public function test_cancel_mockRepository_inactiveTransaction()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'settled',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $mockRepository->expects($this->once())
            ->method('inactiveTransaction')
            ->with($request->TransferCode);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->cancel($request);
    }

    public function test_cancel_mockRepository_createTransaction()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $transactionData = (object) [
            'flag' => 'settled',
            'bet_id' => 'settled-1-testTransactionID',
            'play_id' => 'testPlayerIDu027',
            'currency' => 'IDR',
            'bet_amount' => 100.00,
            'payout_amount' => 300.00,
            'bet_time' => '2024-01-01 00:00:00'
        ];

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($transactionData);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'cancel-1-testTransactionID',
                trxID: 'testTransactionID',
                playID: 'testPlayerIDu027',
                currency: 'IDR',
                betAmount: 100.00,
                betTime: '2024-01-01 00:00:00',
                flag: 'void',
                sportsbookDetails: new SboCancelSportsbookDetails(
                    trxID: 'testTransactionID',
                    ipAddress: '1.2.3.4',
                    transaction: $transactionData,
                ),
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->cancel($request);
    }

    public function test_cancel_mockWallet_resettle()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'settled',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('resettle')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayerIDu027',
                currency: 'IDR',
                transactionID: 'cancel-1-testTransactionID',
                amount: -200.00,
                betID: 'testTransactionID',
                settledTransactionID: 'settled-1-testTransactionID',
                betTime: '2024-01-01 00:00:00'
            )
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
        $service->cancel($request);
    }

    public function test_cancel_stubWalletResettleError_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'settled',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn(['status_code' => 4418468]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->cancel($request);
    }

    public function test_cancel_stubDataRunning_expectedData()
    {
        $expectedData = 1300.00;

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'running',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'game_code' => 0,
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 1300.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->cancel($request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_cancel_stubDataSettled_expectedData()
    {
        $expectedData = 1300.00;

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'sbo_testPlayerIDu027',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'currency' => 'IDR',
                'ip_address' => '1.2.3.4'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'settled',
                'bet_id' => 'settled-1-testTransactionID',
                'play_id' => 'testPlayerIDu027',
                'currency' => 'IDR',
                'bet_amount' => 100.00,
                'payout_amount' => 300.00,
                'bet_time' => '2024-01-01 00:00:00'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 1300.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $response = $service->cancel($request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }
}