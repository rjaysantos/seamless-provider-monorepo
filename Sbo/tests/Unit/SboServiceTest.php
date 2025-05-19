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
use Providers\Sbo\Exceptions\InvalidCompanyKeyException;
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

    // public function
}