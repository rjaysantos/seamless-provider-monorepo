<?php

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
use Providers\Sbo\Exceptions\TransactionAlreadyRollbackException;
use Providers\Sbo\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use Providers\Sbo\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class SboServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $sboApi = null,
        $wallet = null,
        $walletReport = null
    ): SboService {
        $repository ??= $this->createMock(SboRepository::class);
        $credentials ??= $this->createMock(SboCredentials::class);
        $sboApi ??= $this->createMock(SboApi::class);
        $wallet ??= $this->createMock(IWallet::class);
        $walletReport ??= $this->createMock(WalletReport::class);

        return new SboService(
            repository: $repository,
            credentials: $credentials,
            sboApi: $sboApi,
            wallet: $wallet,
            walletReport: $walletReport
        );
    }

    public function test_rollback_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->Username)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'payout-1-testTransactionID',
                'bet_amount' => 1000.0,
                'payout_amount' => 2000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'settled'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->rollback(request: $request);
    }

    public function test_rollback_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $mockCredentials = $this->createMock(SboCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'payout-1-testTransactionID',
                'bet_amount' => 1000.0,
                'payout_amount' => 2000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'settled'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_invalidCompanyKey_InvalidCompanyKeyException()
    {
        $this->expectException(InvalidCompanyKeyException::class);

        $request = new Request([
            'CompanyKey' => 'invalidCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->rollback(request: $request);
    }

    public function test_rollback_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->TransferCode)
            ->willReturn((object) [
                'bet_id' => 'payout-1-testTransactionID',
                'bet_amount' => 1000.0,
                'payout_amount' => 2000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'settled'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_mockWallet_balance()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                $providerCredentials,
                'testPlayID'
            )
            ->willReturn([
                'credit' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_walletErrorBalance_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 999
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_stubRepository_ProviderTransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_stubRepository_TransactionAlreadyRollbackException()
    {
        $this->expectException(TransactionAlreadyRollbackException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'rollback-1-testTransactionID',
                'bet_amount' => 1000.0,
                'payout_amount' => 0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'rollback'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_mockRepository_getRollbackCount()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'payout-1-testTransactionID',
                'bet_amount' => 1000.0,
                'payout_amount' => 2000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'settled'
            ]);

        $mockRepository->expects($this->once())
            ->method('getRollbackCount')
            ->with(trxID: $request->TransferCode)
            ->willReturn(1);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_mockRepository_createRollbackTransaction()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $transactionData = [
            'bet_id' => 'payout-1-testTransactionID',
            'bet_amount' => 1000.0,
            'payout_amount' => 2000.0,
            'bet_time' => '2020-01-02 00:00:00',
            'flag' => 'settled'
        ];

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) $transactionData);

        $mockRepository->expects($this->once())
            ->method('createRollbackTransaction')
            ->with(
                trxID: $request->TransferCode,
                betID: 'rollback-1-testTransactionID',
                transactionData: (object) $transactionData
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_mockWallet_resettle()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'payout-1-testTransactionID',
                'bet_amount' => 1000.0,
                'payout_amount' => 2000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'settled'
            ]);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('resettle')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'rollback-1-testTransactionID',
                amount: -2000.0,
                betID: 'testTransactionID',
                settledTransactionID: 'payout-1-testTransactionID',
                betTime: '2020-01-02 00:00:00'
            )
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_walletErrorResettle_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'payout-1-testTransactionID',
                'bet_amount' => 1000.0,
                'payout_amount' => 2000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'settled'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 999
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->rollback(request: $request);
    }

    public function test_settle_stubWalletResettle_expectedData()
    {
        $expectedData = 2200.00;

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'payout-1-testTransactionID',
                'bet_amount' => 1000.0,
                'payout_amount' => 2000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'settled'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 2200.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $result = $service->rollback(request: $request);

        $this->assertSame(expected: $expectedData, actual: $result);
    }
}
