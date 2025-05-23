<?php

use Tests\TestCase;
use Providers\Sbo\SboApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Sbo\SboService;
use Providers\Sbo\SboRepository;
use Providers\Sbo\SboCredentials;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Sbo\Contracts\ICredentials;
use Providers\Sbo\Exceptions\WalletException;
use Providers\Sbo\Exceptions\InsufficientFundException;
use Providers\Sbo\Exceptions\InvalidCompanyKeyException;
use Providers\Sbo\Exceptions\TransactionAlreadyExistException;
use Providers\Sbo\SportsbookDetails\SboRunningSportsbookDetails;
use Providers\Sbo\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;

class SboServiceTest extends TestCase
{
    public function makeService(
        $repository = null, 
        $credentials = null, 
        $sboApi = null, 
        $wallet = null, 
        $walletReport = null
    ): SboService {
        $repository ??= $this->createStub(SboRepository::class);
        $credentials ??= $this->createStub(SboCredentials::class);
        $sboApi ??= $this->createStub(SboApi::class);
        $wallet ??= $this->createStub(IWallet::class);
        $walletReport ??= $this->createStub(WalletReport::class);

        return new SboService(
            repository: $repository, 
            credentials: $credentials, 
            sboApi: $sboApi, 
            wallet: $wallet, 
            walletReport: $walletReport
        );
    }

    public function test_deduct_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('testPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_playerNotFound_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);
        
        $service = $this->makeService(repository: $stubRepository);
        $service->deduct(request: $request);
    }

    public function test_deduct_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $mockCredentials = $this->createMock(SboCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with('IDR')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_invalidCompanyKey_InvalidCompanyKeyException()
    {
        $this->expectException(InvalidCompanyKeyException::class);

        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'invalidCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $mockCredentials = $this->createMock(SboCredentials::class);
        $mockCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $service = $this->makeService(repository: $stubRepository,credentials: $mockCredentials);
        $service->deduct(request: $request);
    }

    public function test_deduct_mockWalletBalance_balance()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $mockWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_walletBalanceResponseCodeNot2100_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->expects($this->once())
            ->method('balance')
            ->willReturn([
                'status_code' => 'invalid'
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_insufficentBalance_InsufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->expects($this->once())
            ->method('balance')
            ->willReturn([
                'credit' => 0.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with($request->TransferCode)
            ->willReturn(null);

        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_transactionAlreadyExist_TransactionAlreadyExistException()
    {
        $this->expectException(TransactionAlreadyExistException::class);

        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object)[
                'trx_id' => 'testTransactionID'
            ]);
            
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository,credentials: $stubCredentials,wallet: $stubWallet);
        $service->deduct(request: $request);
    }

    public function test_deduct_mockRepository_createTransaction()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                'wager-1-testTransactionID',
                $request->TransferCode,
                'testPlayID',
                'IDR',
                $request->Amount,
                '2021-06-01 12:23:25',
                'running',
                (object) [
                    'gameCode' => 0,
                    'betChoice' => '-',
                    'result' => '-',
                    'event' => '-',
                    'match' => '-',
                    'market' => '-',
                    'hdp' => '-',
                    'odds' => '0',
                    'opt' => '-',
                    'sportsType' => '-' 
                ]
            );

        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_mockWalletReport_makeSportsbookReport()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSportsbookReport')
            ->with(
                $request->TransferCode,
                '2021-06-01 12:23:25',
                new SboRunningSportsbookDetails(gameCode: 0)
            )
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $mockWalletReport
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_mockWallet_wager()
    {
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                $credentials,
                'testPlayID',
                'IDR',
                'wager-1-testTransactionID',
                $request->Amount,
                new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $mockWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);  
   
        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_walletWagerResponseCodeNot2100_WalletException()
    {
        $this->expectException(WalletException::class);
        
        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);  

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 'invalid',
            ]);
   
        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );

        $service->deduct(request: $request);
    }

    public function test_deduct_stubWalletResponse_expected()
    {
        $expected = 1000.0;

        $request = new Request([
            'Amount' => 100.00,
            'TransferCode' => 'testTransactionID',
            'BetTime' => '2021-06-01T00:23:25.9143053-04:00',
            'CompanyKey' => 'sampleCompanyKey',
            'Username' => 'testPlayID',
            'GameId' => 0,
            'ProductType' => 1
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);
        
        $credentials = $this->createMock(ICredentials::class);
        $credentials->method('getCompanyKey')
            ->willReturn('sampleCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);  

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);
   
        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );

        $result = $service->deduct(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }
}