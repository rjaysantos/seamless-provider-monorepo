<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\GameProviders\V2\Sab\SabApi;
use Illuminate\Support\Facades\Crypt;
use Wallet\V1\ProvSys\Transfer\Report;
use App\GameProviders\V2\Sab\SabService;
use App\Libraries\Wallet\V2\WalletReport;
use App\GameProviders\V2\Sab\SabRepository;
use App\GameProviders\V2\Sab\SabCredentials;
use App\GameProviders\V2\Sab\Contracts\ICredentials;
use App\GameProviders\V2\Sab\Credentials\SabStagingKCurrency;
use App\GameProviders\V2\Sab\SportsbookDetails\SabSettleSportsbookDetails;
use App\GameProviders\V2\Sab\SportsbookDetails\SabNumberGameSportsbookDetails;
use App\GameProviders\V2\Sab\SportsbookDetails\SabSettleParlaySportsbookDetails;
use App\GameProviders\V2\Sab\Exceptions\WalletException;
use App\GameProviders\V2\Sab\Exceptions\InvalidKeyException;
use App\GameProviders\V2\Sab\Exceptions\InvalidTransactionStatusException;
use App\GameProviders\V2\Sab\SportsbookDetails\SabRunningSportsbookDetails;
use App\GameProviders\V2\Sab\Exceptions\InsufficientFundException;
use App\GameProviders\V2\Sab\Exceptions\TransactionAlreadyExistException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use App\GameProviders\V2\Sab\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\GameProviders\V2\Sab\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class SabServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $wallet = null,
        $walletReport = null
    ): SabService {
        $repository ??= $this->createMock(SabRepository::class);
        $credentials ??= $this->createMock(SabCredentials::class);
        $api ??= $this->createMock(SabApi::class);
        $api ??= $this->createMock(SabApi::class);
        $wallet ??= $this->createMock(IWallet::class);
        $walletReport ??= $this->createMock(WalletReport::class);

        return new SabService(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            wallet: $wallet,
            walletReport: $walletReport
        );
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ]);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getSabaUrl')
            ->willReturn('testLaunchUrl.com');

        $service = $this->makeService(credentials: $mockCredentials, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getSabaUrl')
            ->willReturn('testLaunchUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getOperatorID')
            ->willReturn('AIX');
        $providerCredentials->method('getSuffix')
            ->willReturn('_test');

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(
                playID: $request->playId,
                currency: $request->currency,
                username: 'AIX_testPlayID_test'
            );

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getSabaUrl')
            ->willReturn('testLaunchUrl.com');

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            api: $stubApi
        );

        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_createMember()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $providerCredentials = $this->createMock(SabStagingKCurrency::class);
        $providerCredentials->method('getOperatorID')
            ->willReturn('AIX');
        $providerCredentials->method('getSuffix')
            ->willReturn('_test');
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getCurrency')
            ->willReturn(20);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('createPlayer');

        $mockApi = $this->createMock(SabApi::class);
        $mockApi->expects($this->once())
            ->method('createMember')
            ->with(
                credentials: $providerCredentials,
                username: 'AIX_testPlayID_test'
            );

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            api: $mockApi
        );

        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_getSabaUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getOperatorID')
            ->willReturn('AIX');
        $providerCredentials->method('getSuffix')
            ->willReturn('_test');
        $providerCredentials->method('getVendorID')
            ->willReturn('testVendorID');
        $providerCredentials->method('getCurrency')
            ->willReturn(20);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('createPlayer');

        $mockApi = $this->createMock(SabApi::class);
        $mockApi->expects($this->once())
            ->method('getSabaUrl')
            ->with(
                credentials: $providerCredentials,
                username: 'AIX_testPlayID_test',
                device: 1
            )
            ->willReturn('testLaunchUrl.com');

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            api: $mockApi
        );

        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expected()
    {
        $baseUrl = 'testLaunchUrl.com';
        $expected = "{$baseUrl}&lang=en&OType=3";

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'device' => 1
        ]);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getSabaUrl')
            ->willReturn($baseUrl);

        $service = $this->makeService(api: $stubApi);
        $result = $service->getLaunchUrl(request: $request);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBetDetailUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID'
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->play_id)
            ->willReturn((object) ['play_id' => 'testPlayer']);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['transaction_id' => 'testTransactionID']);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_nullPlayer_PlayerNotFoundException()
    {
        $this->expectException(CasinoPlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID'
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayer']);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->bet_id)
            ->willReturn((object) ['transaction_id' => 'testTransaction']);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_nullTransaction_TransactionNotFoundException()
    {
        $this->expectException(CasinoTransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID'
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayer']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubService_expectedData()
    {
        $encryptedTrxID = 'testEncryptedTrxID';
        $decryptedTrxID = 'testTransactionID';

        Crypt::shouldReceive('encryptString')
            ->andReturn($encryptedTrxID);

        Crypt::shouldReceive('decryptString')
            ->andReturn($decryptedTrxID);

        $expected = 'https://localhost/sab/in/visual/' . $encryptedTrxID;

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => $decryptedTrxID
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['transaction_id' => $decryptedTrxID]);

        $service = $this->makeService(repository: $stubRepository);
        $result = $service->getBetDetailUrl(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }


    public function test_getBetDetailData_mockRepository_getTransactionByTrxID()
    {
        $encryptedTrxID = 'testEncryptedTrxID';
        $decryptedTxID = 'testDecryptedTrxID';

        Crypt::shouldReceive('decryptString')
            ->andReturn($decryptedTxID);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $decryptedTxID)
            ->willReturn((object) [
                'transaction_id' => $decryptedTxID,
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '127.0.0.1',
            ]);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 161,
            ]);

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);
    }

    public function test_getBetDetailData_stubRepository_TransactionNotFoundException()
    {
        $this->expectException(CasinoTransactionNotFoundException::class);

        $encryptedTrxID = 'testEncryptedTrxID';
        $decryptedTxID = 'testDecryptedTrxID';

        Crypt::shouldReceive('decryptString')
            ->andReturn($decryptedTxID);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);
    }

    public function test_getBetDetailData_mockCredentials_getCredentialsByCurrency()
    {
        $encryptedTrxID = 'testEncryptedTrxID';
        $decryptedTxID = 'testDecryptedTrxID';

        Crypt::shouldReceive('decryptString')
            ->andReturn($decryptedTxID);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'transaction_id' => $decryptedTxID,
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '127.0.0.1',
            ]);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 161,
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            api: $stubApi,
            credentials: $mockCredentials
        );

        $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);
    }

    public function test_getBetDetailData_mockApi_getBetDetail()
    {
        $encryptedTrxID = 'testEncryptedTrxID';
        $decryptedTxID = 'testDecryptedTrxID';

        Crypt::shouldReceive('decryptString')
            ->andReturn($decryptedTxID);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'transaction_id' => $decryptedTxID,
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'ip_address' => '127.0.0.1',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(SabApi::class);
        $mockApi->expects($this->once())
            ->method('getBetDetail')
            ->with(credentials: $providerCredentials, transactionID: $decryptedTxID)
            ->willReturn((object) [
                'sport_type' => 161,
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            api: $mockApi,
            credentials: $stubCredentials
        );

        $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);
    }

    public function test_getBetDetailData_stubApiNumberGame_expectedData()
    {
        $expected = new SabNumberGameSportsbookDetails(
            sabResponse: (object) ['sport_type' => 161],
            ipAddress: '127.0.0.1',
        );

        $encryptedTrxID = 'testEncryptedTrxID';
        $decryptedTxID = 'testDecryptedTxID';

        Crypt::shouldReceive('decryptString')
            ->andReturn($decryptedTxID);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR',
                'trx_id' => $decryptedTxID,
                'ip_address' => '127.0.0.1',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 161,
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            api: $stubApi,
            credentials: $stubCredentials
        );

        $result = $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBetDetailData_stubSettleSportsbook_expectedData()
    {
        $expected = new SabSettleSportsbookDetails(
            sabResponse: (object) ['ParlayData' => null, 'sport_type' => 1],
            ipAddress: '127.0.0.1',
        );

        $encryptedTrxID = 'testEncryptedTrxID';
        $decryptedTxID = 'testDecryptedTxID';

        Crypt::shouldReceive('decryptString')
            ->andReturn($decryptedTxID);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'trx_id' => $decryptedTxID,
                'currency' => 'IDR',
                'ip_address' => '127.0.0.1',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'ParlayData' => null,
                'sport_type' => 1
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            api: $stubApi,
            credentials: $stubCredentials
        );

        $result = $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBetDetailData_stubSettleParlaySportsbook_expectedData()
    {
        $expected = new SabSettleParlaySportsbookDetails(
            sabResponse: (object) ['ParlayData' => (object) [], 'sport_type' => 1],
            ipAddress: '127.0.0.1',
        );

        $encryptedTrxID = 'testEncryptedTrxID';
        $decryptedTxID = 'testDecryptedTxID';

        Crypt::shouldReceive('decryptString')
            ->andReturn($decryptedTxID);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'trx_id' => $decryptedTxID,
                'currency' => 'IDR',
                'ip_address' => '127.0.0.1',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'ParlayData' => (object) [],
                'sport_type' => 1
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            api: $stubApi,
            credentials: $stubCredentials
        );

        $result = $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBalance_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUsername')
            ->with(username: $request->message['userId'])
            ->willReturn((object) [
                'play_id' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubRepository_PlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_invalidVendorID_InvalidKeyException()
    {
        $this->expectException(InvalidKeyException::class);

        $request = new Request([
            'key' => 'invalid-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('valid-test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockWallet_balance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: 'test-player-username')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_invalidWalletResponse_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 0,
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWallet_expectedData()
    {
        $expected = 1000.0;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'test-player-username',
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->expects($this->once())
            ->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $response = $service->getBalance(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_placeBet_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUsername')
            ->with(username: $request->message['userId'])
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_nullPlayer_PlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->placeBet(request: $request);
    }

    public function test_placeBet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_invalidVendorID_InvalidKeyException()
    {
        $this->expectException(InvalidKeyException::class);

        $request = new Request([
            'key' => 'invalid-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $service = $this->makeService(repository: $stubRepository);
        $service->placeBet(request: $request);
    }

    public function test_placeBet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->with(username: $request->message['userId'])
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->message['refId']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_transactionNotNull_TransactionAlreadyExistException()
    {
        $this->expectException(TransactionAlreadyExistException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'status' => 1
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->placeBet(request: $request);
    }

    public function test_placeBet_mockWallet_balance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: 'testPlayID')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 900.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_invalidWalletResponse_walletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1000,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999,
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_insufficientFund_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 900.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'wager-1-testTransactionID',
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'testTransactionID',
                betAmount: 1000,
                betTime: '2020-01-01 12:00:00',
                gameCode: 1,
                ip: '123.456.7.8',
                flag: 'waiting'
            );

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_stubRepository_expected()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'refId' => 'testTransactionID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'actualAmount' => 1,
                'betType' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $result = $service->placeBet(request: $request);

        $this->assertNull(actual: $result);
    }

    public function test_confirmBet_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUsername')
            ->with(username: $request->message['userId'])
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('createTransaction')
            ->willReturn([
                'bet_id' => "confirmBet-1-12345",
                'bet_amount' => 1000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_nullPlayer_PlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials,
            walletReport: $stubReport
        );

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_invalidVendorID_InvalidKeyException()
    {
        $this->expectException(InvalidKeyException::class);

        $request = new Request([
            'key' => 'invalid-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('valid-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->message['txns'][0]['refId'])
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'status' => 1,
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('createTransaction')
            ->willReturn([
                'bet_id' => "confirmBet-1-12345",
                'bet_amount' => 1000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_nullTransaction_TransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_invalidStatus_InvalidTransactionStatusException()
    {
        $this->expectException(InvalidTransactionStatusException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'running'
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'trx_id' => 'testTransactionID',
            'flag' => 'waiting',
            'game_code' => 1,
            'ip_address' => '123.456.7.8'
        ];

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: "confirmBet-1-12345",
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: '12345',
                betAmount: 1000.0,
                betTime: '2021-01-01 12:00:00',
                gameCode: '1',
                ip: '123.456.7.8',
                flag: 'running'
            )
            ->willReturn([
                'bet_id' => "confirmBet-1-12345",
                'trx_id' => 12345,
                'bet_amount' => 1000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_mockReport_makeSportsbookReport()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $newTransactionDetails = [
            'bet_id' => "confirmBet-1-12345",
            'trx_id' => 12345,
            'bet_amount' => 1000.0,
            'flag' => 'waiting'
        ];

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('createTransaction')
            ->willReturn($newTransactionDetails);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSportsbookReport')
            ->with(
                transaction: $newTransactionDetails,
                sportsbookDetails: new SabRunningSportsbookDetails
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $mockReport
        );

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_mockWallet_wager()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testTransactionID',
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('createTransaction')
            ->willReturn([
                'bet_id' => "confirmBet-1-12345",
                'bet_amount' => 1000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'confirmBet-1-12345',
                amount: 1000.0,
                report: new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_invalidWalletWagerResponse_WalletException()
    {
        $this->expectException(WalletException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('createTransaction')
            ->willReturn([
                'bet_id' => "confirmBet-1-12345",
                'bet_amount' => 1000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 'invalid',
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_stubWallet_expected()
    {
        $expected = 1.0;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'txId' => 12345,
                        'actualAmount' => 1
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('currencyConversion')
            ->willReturn(1000.0);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('createTransaction')
            ->willReturn([
                'bet_id' => "confirmBet-1-12345",
                'bet_amount' => 1000.0
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $result = $service->confirmBet(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }
}
