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
use Providers\Sbo\Exceptions\InvalidCompanyKeyException;
use Providers\Sbo\Exceptions\TransactionAlreadyVoidException;
use Providers\Sbo\SportsbookDetails\SboSettleSportsbookDetails;
use Providers\Sbo\Exceptions\TransactionAlreadySettledException;
use Providers\Sbo\Exceptions\ProviderTransactionNotFoundException;
use Providers\Sbo\SportsbookDetails\SboSettleParlaySportsbookDetails;
use Providers\Sbo\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;

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

    public function test_settle_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->Username)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
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
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $mockCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_invalidCompanyKey_InvalidCompanyKeyException()
    {
        $this->expectException(InvalidCompanyKeyException::class);

        $request = new Request([
            'CompanyKey' => 'invalidCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
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
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_balance()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->never())
            ->method('balance')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID'
            )
            ->willReturn([
                'credit' => 2000.0,
                'status_code' => 2100
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $mockWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_walletErrorBalance_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
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

        $service->settle(request: $request);
    }

    public function test_settle_stubRepository_ProviderTransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
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
                'credit' => 1200.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->settle(request: $request);
    }

    public function test_settle_stubRepository_TransactionAlreadySettledException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'settled',
                'ip_address' => '123.456.7.8',
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1200.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->settle(request: $request);
    }

    public function test_settle_stubRepository_TransactionAlreadyVoidException()
    {
        $this->expectException(TransactionAlreadyVoidException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'void',
                'ip_address' => '123.456.7.8',
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1200.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockApi_getBetList()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $mockApi = $this->createMock(SboApi::class);
        $mockApi->expects($this->once())
            ->method('getBetList')
            ->with(credentials: $providerCredentials, trxID: $request->TransferCode)
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            sboApi: $mockApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_SboSettleParlaySportsbookDetails()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'sportsType' => 'Mix Parlay',
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $mockRepository->expects($this->once())
            ->method('createSettleTransaction')
            ->with(
                trxID: 'testTransactionID',
                betID: "payout-1-testTransactionID",
                playID: 'testPlayID',
                currency: 'IDR',
                betAmount: 1000.0,
                payoutAmount: 1200.0,
                settleTime: '2020-01-02 12:00:00',
                sportsbookDetails: new SboSettleParlaySportsbookDetails(
                    request: $request,
                    betAmount: 1000.0,
                    odds: 5.70,
                    oddsStyle: 'E',
                    ipAddress: '123.456.7.8',
                )
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_SboSettleSportsbookDetails()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $apiResponse = [
            'subBet' => [
                (object) [
                    'match' => 'TeamA vs TeamB',
                    'betOption' => 1,
                    'marketType' => 'First Half Handicap',
                    'sportType' => 'Soccer',
                    'league' => 'Premier League',
                    'hdp' => '0.5',
                    'odds' => 5.70
                ]
            ],
            'oddsStyle' => 'E'
        ];

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) $apiResponse);

        $mockRepository->expects($this->once())
            ->method('createSettleTransaction')
            ->with(
                trxID: 'testTransactionID',
                betID: "payout-1-testTransactionID",
                playID: 'testPlayID',
                currency: 'IDR',
                betAmount: 1000.0,
                payoutAmount: 1200.0,
                settleTime: '2020-01-02 12:00:00',
                sportsbookDetails: new SboSettleSportsbookDetails(
                    betDetails: (object) $apiResponse,
                    request: $request,
                    betAmount: 1000.0,
                    ipAddress: '123.456.7.8',
                )
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_getRollbackCount()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'rollback-1-testTransactionID',
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'payout_amount' => 0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'rollback',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $mockRepository->expects($this->once())
            ->method('getRollbackCount')
            ->with(trxID: $request->TransferCode)
            ->willReturn(1);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_getSettleCount()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $mockRepository->expects($this->once())
            ->method('getSettleCount')
            ->with(trxID: $request->TransferCode)
            ->willReturn(1);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_createSettleTransaction()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $apiResponse = [
            'subBet' => [
                (object) [
                    'match' => 'TeamA vs TeamB',
                    'betOption' => 1,
                    'marketType' => 'First Half Handicap',
                    'sportType' => 'Soccer',
                    'league' => 'Premier League',
                    'hdp' => '0.5',
                    'odds' => 5.70
                ]
            ],
            'oddsStyle' => 'E'
        ];

        $mockRepository = $this->createMock(SboRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) $apiResponse);

        $mockRepository->expects($this->once())
            ->method('createSettleTransaction')
            ->with(
                trxID: 'testTransactionID',
                betID: "payout-1-testTransactionID",
                playID: 'testPlayID',
                currency: 'IDR',
                betAmount: 1000.0,
                payoutAmount: 1200.0,
                settleTime: '2020-01-02 12:00:00',
                sportsbookDetails: new SboSettleSportsbookDetails(
                    betDetails: (object) $apiResponse,
                    request: $request,
                    betAmount: 1000.0,
                    ipAddress: '123.456.7.8',
                )
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_resettle()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
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
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'payout_amount' => 0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'rollback',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $stubRepository->method('getRollbackCount')
            ->willReturn(1);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('resettle')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'resettle-1-testTransactionID',
                amount: 1200.0,
                betID: 'testTransactionID',
                settledTransactionID: 'rollback-1-testTransactionID',
                betTime: '2020-01-02 12:00:00'
            )
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockReport_makeSportsbookReport()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $apiResponse = [
            'subBet' => [
                (object) [
                    'match' => 'TeamA vs TeamB',
                    'betOption' => 1,
                    'marketType' => 'First Half Handicap',
                    'sportType' => 'Soccer',
                    'league' => 'Premier League',
                    'hdp' => '0.5',
                    'odds' => 5.70
                ]
            ],
            'oddsStyle' => 'E'
        ];

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) $apiResponse);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSportsbookReport')
            ->with(
                trxID: 'testTransactionID',
                betTime: '2020-01-02 12:00:00',
                sportsbookDetails: new SboSettleSportsbookDetails(
                    betDetails: (object) $apiResponse,
                    request: $request,
                    betAmount: 1000.0,
                    ipAddress: '123.456.7.8',
                )
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $mockReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_payout()
    {
        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'payout-1-testTransactionID',
                amount: 1200.0,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 2000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_walletErrorResettle_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
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
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'payout_amount' => 0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'rollback',
                'ip_address' => '123.456.7.8',
            ]);

        $stubRepository->method('getRollbackCount')
            ->willReturn(1);

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

        $service->settle(request: $request);
    }

    public function test_settle_walletErrorPayout_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 999
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_stubWalletResettle_expectedData()
    {
        $expectedData = 2200.00;

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
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
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'payout_amount' => 0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'rollback',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $stubRepository->method('getRollbackCount')
            ->willReturn(1);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 2200.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
        );

        $result = $service->settle(request: $request);

        $this->assertSame(expected: $expectedData, actual: $result);
    }

    public function test_settle_stubWalletPayout_expectedData()
    {
        $expectedData = 2200.0;

        $request = new Request([
            'CompanyKey' => 'testCompanyKey',
            'Username' => 'testPlayID',
            'TransferCode' => 'testTransactionID',
            'WinLoss' => 1200.0,
            'ResultTime' => '2020-01-02 00:00:00',
            'IsCashOut' => false
        ]);

        $stubRepository = $this->createMock(SboRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getCompanyKey')
            ->willReturn('testCompanyKey');

        $stubCredentials = $this->createMock(SboCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'game_code' => 0,
                'bet_amount' => 1000.0,
                'bet_time' => '2020-01-02 00:00:00',
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $stubApi = $this->createMock(SboApi::class);
        $stubApi->method('getBetList')
            ->willReturn((object) [
                'subBet' => [],
                'oddsStyle' => 'E',
                'odds' => 5.70
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 2200.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            sboApi: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $result = $service->settle(request: $request);

        $this->assertSame(expected: $expectedData, actual: $result);
    }
}
