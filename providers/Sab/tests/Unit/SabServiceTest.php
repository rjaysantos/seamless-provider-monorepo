<?php

use Tests\TestCase;
use Providers\Sab\SabApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Sab\SabService;
use Providers\Sab\SabRepository;
use Providers\Sab\SabCredentials;
use Illuminate\Support\Facades\Crypt;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Sab\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Sab\Exceptions\InvalidKeyException;
use Providers\Sab\Credentials\SabStagingKCurrency;
use Providers\Sab\Exceptions\WalletErrorException;
use App\Exceptions\Casino\ThirdPartyApiErrorException;
use Providers\Sab\Exceptions\InsufficientFundException;
use Providers\Sab\SportsbookDetails\SabSportsbookDetails;
use Providers\Sab\Exceptions\TransactionAlreadyExistException;
use Providers\Sab\Exceptions\InvalidTransactionStatusException;
use Providers\Sab\SportsbookDetails\SabRunningSportsbookDetails;
use Providers\Sab\SportsbookDetails\SabSettledSportsbookDetails;
use Providers\Sab\Exceptions\ProviderThirdPartyApiErrorException;
use Providers\Sab\SportsbookDetails\SabMixParlaySportsbookDetails;
use Providers\Sab\SportsbookDetails\SabNumberGameSportsbookDetails;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Sab\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use Providers\Sab\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

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
                'trans_id' => 1,
                'settlement_time' => null,
                'odds_type' => 1,
                'stake' => 10,
                'ticket_status' => 'test'
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
                'trans_id' => 1,
                'settlement_time' => null,
                'odds_type' => 1,
                'stake' => 10,
                'ticket_status' => 'test'
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
                'trans_id' => 1,
                'settlement_time' => null,
                'odds_type' => 1,
                'stake' => 10,
                'ticket_status' => 'test'
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            api: $mockApi,
            credentials: $stubCredentials
        );

        $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);
    }

    public function test_getBetDetailData_betDetailSportsTypeNumberGame_expectedData()
    {
        $expected = [
            'ticketID' => 1,
            'dateTimeSettle' => '-',
            'event' => '-',
            'match' => '-',
            'betType' => '-',
            'betChoice' => '-',
            'hdp' => '-',
            'odds' => 0,
            'oddsType' => 'Malay Odds',
            'betAmount' => 10,
            'score' => '-',
            'status' => 'test',
            'mixParlayData' => [],
            'singleParlayData' => []
        ];

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
                'trans_id' => 1,
                'settlement_time' => null,
                'odds_type' => 1,
                'stake' => 10,
                'ticket_status' => 'test'
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            api: $stubApi,
            credentials: $stubCredentials
        );

        $result = $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBetDetailData_betDetailSportsTypeSportsbook_expectedData()
    {
        $expected = [
            'ticketID' => 1,
            'dateTimeSettle' => '-',
            'event' => '-',
            'match' => '-',
            'betType' => 'betTypeName',
            'betChoice' => '-',
            'hdp' => '-',
            'odds' => 0,
            'oddsType' => 'Malay Odds',
            'betAmount' => 10,
            'score' => '-',
            'status' => 'test',
            'mixParlayData' => [],
            'singleParlayData' => []
        ];

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
                'sport_type' => 1,
                'trans_id' => 1,
                'settlement_time' => null,
                'odds_type' => 1,
                'stake' => 10,
                'ticket_status' => 'test',
                'bettypename' => [
                    (object) [
                        'name' => 'betTypeName'
                    ]
                ]
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            api: $stubApi,
            credentials: $stubCredentials
        );

        $result = $service->getBetDetailData(encryptedTrxID: $encryptedTrxID);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBetDetailData_betDetailSportsbookMixParlay_expectedData()
    {
        $expected = [
            'ticketID' => 1,
            'dateTimeSettle' => '-',
            'event' => '-',
            'match' => 'Mix Parlay',
            'betType' => 'betTypeName',
            'betChoice' => '-',
            'hdp' => '-',
            'odds' => 0,
            'oddsType' => 'Malay Odds',
            'betAmount' => 10,
            'score' => '-',
            'status' => '-',
            'mixParlayData' => [
                (object) [
                    'event' => 'leagueName',
                    'match' => '-',
                    'betType' => 'betTypeName',
                    'betChoice' => '-',
                    'hdp' => '-',
                    'odds' => 0,
                    'score' => '-',
                    'status' => 'test',
                ]
            ],
            'singleParlayData' => []
        ];

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
                'sport_type' => 1,
                'trans_id' => 1,
                'settlement_time' => null,
                'odds_type' => 1,
                'stake' => 10,
                'ticket_status' => 'test',
                'ParlayData' => [
                    (object) [
                        'leaguename' => [
                            (object) [
                                'name' => 'leagueName'
                            ]
                        ],
                        'bettypename' => [
                            (object) [
                                'name' => 'betTypeName'
                            ]
                        ],
                        'ticket_status' => 'test',
                    ]
                ],
                'bettypename' => [
                    (object) [
                        'name' => 'betTypeName'
                    ]
                ]
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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

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

    public function test_getBalance_invalidWalletResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
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
            credentials: $stubCredentials
        );

        $response = $service->getBalance(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_getBalance_stubWalletWithCurrencyConversion_expectedData()
    {
        $expected = 1.0;

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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
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
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'operationId' => 'testOperationID',
                'betTime' => '2020-01-01 12:00:00',
                'betType' => 1,
                'actualAmount' => 1,
                'IP' => '123.456.7.8'
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUsername')
            ->with(username: $request->message['userId'])
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'operationId' => 'testOperationID',
                'betTime' => '2020-01-01 12:00:00',
                'betType' => 1,
                'actualAmount' => 1,
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
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'operationId' => 'testOperationID',
                'betTime' => '2020-01-01 12:00:00',
                'betType' => 1,
                'actualAmount' => 1,
                'IP' => '123.456.7.8'
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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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
                'operationId' => 'testOperationID',
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
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
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
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: 'testTransactionID');

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_transactionAlreadyExists_transactionAlreadyExistsException()
    {
        $this->expectException(TransactionAlreadyExistException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
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
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_mockWallet_balance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
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
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: 'testPlayID')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_invalidWalletResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
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
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_mockRepository_getWaitingBetAmountByPlayID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
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
            ->method('getWaitingBetAmountByPlayID')
            ->with(playID: 'testPlayID')
            ->willReturn(0.0);

        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_insufficientFunds_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
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
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_hasWaitingBetAmountInsufficientFunds_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
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
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getWaitingBetAmountByPlayID')
            ->willReturn(1000.0);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
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
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationID-testTransactionID',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: 'testTransactionID',
                betAmount: 1000,
                payoutAmount: 0,
                betDate: '2020-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'waiting',
                sportsbookDetails: new SabRunningSportsbookDetails(gameCode: 1)
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBet(request: $request);
    }

    public function test_placeBet_stubRepository_expectedData()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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

    public function test_placeBetParlay_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUsername')
            ->with(username: $request->message['userId'])
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_nullPlayer_PlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_invalidVendorID_InvalidKeyException()
    {
        $this->expectException(InvalidKeyException::class);

        $request = new Request([
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials
        );

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
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
            ->with(trxID: 'testTransactionID');

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_transactionAlreadyExists_transactionAlreadyExistsException()
    {
        $this->expectException(TransactionAlreadyExistException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
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
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_mockRepository_getWaitingBetAmountByPlayID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getWaitingBetAmountByPlayID')
            ->willReturn(0.0);

        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_mockWallet_balance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: 'testPlayID')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet
        );

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_invalidWalletResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_insufficientFunds_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_withWaitingBetAmountInsufficientFunds_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getWaitingBetAmountByPlayID')
            ->willReturn(1000.0);

        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
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

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationID-testTransactionID',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: 'testTransactionID',
                betAmount: 1000,
                payoutAmount: 0,
                betDate: '2020-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'waiting',
                sportsbookDetails: new SabRunningSportsbookDetails(gameCode: 'Mix Parlay')
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1100.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );

        $service->placeBetParlay(request: $request);
    }

    public function test_placeBetParlay_stubRepository_expectedData()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'betTime' => '2020-01-01T00:00:00.000-04:00',
                'totalBetAmount' => 1,
                'IP' => '123.456.7.8',
                'txns' => [
                    [
                        'refId' => 'testTransactionID',
                        'betAmount' => 1
                    ],
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

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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

        $result = $service->placeBetParlay(request: $request);

        $this->assertNull(actual: $result);
    }

    public function test_confirmBet_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
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
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

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
                'operationId' => 'testOperationId',
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
                'operationId' => 'testOperationId',
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
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_mockWallet_balance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
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
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: 'testPlayID')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $mockWallet->method('wager')
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

    public function test_confirmBet_invalidWalletBalanceResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
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
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 'invalid'
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_insufficientFunds_WalletErrorException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
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
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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

        $service->confirmBet(request: $request);
    }

    public function test_confirmBet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
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

        $mockRepository->expects($this->exactly(2))
            ->method('getTransactionByTrxID')
            ->willReturnOnConsecutiveCalls((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

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
                'operationId' => 'testOperationId',
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
            ->willReturnOnConsecutiveCalls(null, null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);


        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->confirmBet(request: $request);
    }

    #[DataProvider('invalidStatusForConfirmBet')]
    public function test_confirmBet_invalidTransactionStatus_InvalidTransactionStatusException($status)
    {
        $this->expectException(InvalidTransactionStatusException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
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
            ->willReturnOnConsecutiveCalls((object) [
                'flag' => $status,
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);


        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->confirmBet(request: $request);
    }

    public static function invalidStatusForConfirmBet()
    {
        return [
            ['settled'],
            ['resettle'],
            ['running'],
            ['unsettle'],
            ['cancelled'],
            ['bonus']
        ];
    }

    public function test_confirmBet_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
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

        $mockRepository->method('getTransactionByTrxID')
            ->willReturnOnConsecutiveCalls((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ], null);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationId-12345',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: 12345,
                betAmount: 1000,
                payoutAmount: 0,
                betDate: '2021-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'running',
                sportsbookDetails: new SabRunningSportsbookDetails(gameCode: 1)
            );

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

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
                'operationId' => 'testOperationId',
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
            ->willReturnOnConsecutiveCalls((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSportsbookReport')
            ->with(
                trxID: 12345,
                betTime: '2021-01-01 12:00:00',
                sportsbookDetails: new SabRunningSportsbookDetails(gameCode: 1)
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

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
                'operationId' => 'testOperationId',
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
            ->willReturnOnConsecutiveCalls((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: "wager-testOperationId-12345",
                amount: 1000,
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

    public function test_confirmBet_invalidWalletWagerResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
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
            ->willReturnOnConsecutiveCalls((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 9999
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
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
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

        $expected = 1.0;

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturnOnConsecutiveCalls((object) [
                'flag' => 'waiting',
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ], null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 2000.0
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000
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

    public function test_cancelBet_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
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
                'bet_id' => 'testBetOperationID-testTransactionID',
                'trx_id' => 'testTransactionID',
                'bet_amount' => 1000,
                'game_code' => 1,
                'flag' => 'waiting',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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
            credentials: $stubCredentials,
        );

        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_nullPlayer_PlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
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
                'bet_id' => 'testOperationID-placebet',
                'trx_id' => 'testTransactionID',
                'flag' => 'waiting',
                'bet_amount' => 1000,
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
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
            credentials: $mockCredentials,
        );

        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_invalidVendorID_InvalidKeyException()
    {
        $this->expectException(InvalidKeyException::class);

        $request = new Request([
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
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
                'bet_id' => 'testBetOperationID-testTransactionID',
                'trx_id' => 'testTransactionID',
                'flag' => 'waiting',
                'bet_amount' => 1000,
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('valid-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: 'testTransactionID')
            ->willReturn((object) [
                'bet_id' => 'testBetOperationID-testTransactionID',
                'trx_id' => 'testTransactionID',
                'flag' => 'waiting',
                'bet_amount' => 1000,
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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
            credentials: $stubCredentials,
        );

        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_nullTransaction_TransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
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
            ->willReturn(null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->cancelBet(request: $request);
    }

    #[DataProvider('invalidStatusForCancelBet')]
    public function test_cancelBet_invalidTransactionStatus_InvalidTransactionStatusException($status)
    {
        $this->expectException(InvalidTransactionStatusException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
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
                'bet_id' => 'testBetOperationID-testTransactionID',
                'trx_id' => 'testTransactionID',
                'flag' => $status,
                'bet_amount' => 1000,
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->cancelBet(request: $request);
    }

    public static function invalidStatusForCancelBet()
    {
        return [
            ['settled'],
            ['resettle'],
            ['running'],
            ['unsettle'],
            ['cancelled'],
            ['bonus']
        ];
    }


    public function test_cancelBet_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
                    ]
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'test-player-username',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'testBetOperationID-testTransactionID',
                'trx_id' => 'testTransactionID',
                'flag' => 'waiting',
                'bet_amount' => 1000,
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationID-testTransactionID',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: 'testTransactionID',
                betAmount: 1000,
                payoutAmount: 0,
                betDate: '2021-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'cancelled',
                sportsbookDetails: new SabRunningSportsbookDetails(1)
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_mockWallet_balance()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
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
                'bet_id' => 'testBetOperationID-testTransactionID',
                'trx_id' => 'testTransactionID',
                'flag' => 'waiting',
                'bet_amount' => 1000,
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials,
        );

        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_invalidWalletBalanceResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
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
                'bet_id' => 'testBetOperationID-testTransactionID',
                'trx_id' => 'testTransactionID',
                'flag' => 'waiting',
                'bet_amount' => 1000,
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 'invalid',
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->cancelBet(request: $request);
    }

    public function test_cancelBet_stubWallet_expected()
    {
        $expected = 1.0;

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'userId' => 'testUsername',
                'updateTime' => '2021-01-01T00:00:00.000-04:00',
                'txns' => [
                    [
                        'refId' => 'testTransactionID'
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
                'bet_id' => 'testBetOperationID-testTransactionID',
                'trx_id' => 'testTransactionID',
                'flag' => 'waiting',
                'bet_amount' => 1000,
                'game_code' => 1,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

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
            credentials: $stubCredentials,
        );

        $result = $service->cancelBet(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }

    public function test_getRunningTransactions_mockRepository_getAllRunningTransactions()
    {
        $request = new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getAllRunningTransactions')
            ->with(
                webID: $request->branchId,
                currency: $request->currency,
                start: $request->start,
                length: $request->length
            )
            ->willReturn((object) [
                'totalCount' => 0,
                'data' => collect()
            ]);

        $service = $this->makeService(repository: $mockRepository);
        $service->getRunningTransactions($request);
    }

    public function test_getRunningTransactions_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getAllRunningTransactions')
            ->willReturn((object) [
                'totalCount' => 0,
                'data' => collect()
            ]);

        $service = $this->makeService(credentials: $mockCredentials, repository: $stubRepository);
        $service->getRunningTransactions($request);
    }

    public function test_getRunningTransactions_mockApi_getBetDetail()
    {
        $request = new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $credentials = $this->createMock(ICredentials::class);
        $transactionID = 1;

        $mockApi = $this->createMock(SabApi::class);
        $mockApi->expects($this->once())
            ->method('getBetDetail')
            ->with(credentials: $credentials, transactionID: $transactionID)
            ->willReturn((object) [
                'sport_type' => 161,
                'odds' => 1,
                'odds_type' => 1
            ]);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn(
                $credentials,
            );

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getAllRunningTransactions')
            ->willReturn((object) [
                'totalCount' => 1,
                'data' => [
                    (object) [
                        'trx_id' => $transactionID,
                        'web_id' => 2,
                        'play_id' => 'test-play-id',
                        'bet_time' => '2020-01-01 00:00:00',
                        'ip_address' => '192.168.1.1',
                        'bet_amount' => 100.0,
                    ]
                ]
            ]);

        $service = $this->makeService(credentials: $stubCredentials, repository: $stubRepository, api: $mockApi);
        $service->getRunningTransactions($request);
    }

    public function test_getRunningTransactions_stubGetBetDetailSportTypeNumberGame_expectedData()
    {
        $expectedData = (object) [
            'totalCount' => 1,
            'data' => [
                [
                    'id' => 1,
                    'bet_id' => 1,
                    'branch_id' => 2,
                    'play_id' => 'test-play-id',
                    'bet_time' => '2020-01-01 00:00:00',
                    'bet_ip' => '192.168.1.1',
                    'amount' => 100.0,
                    'game_type' => '-',
                    'league' => '-',
                    'match' => '-',
                    'bet_option' => '-',
                    'hdp' => '-',
                    'odds' => 1,
                    'odds_type' => 'Malay Odds',
                    'sports_type' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'detail_link' => url('/') . '/sab/in/visual/' . 'test-encrypt',
                ]
            ]
        ];

        Crypt::shouldReceive('encryptString')
            ->andReturn('test-encrypt');

        $request = new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getAllRunningTransactions')
            ->willReturn((object) [
                'totalCount' => 1,
                'data' => [
                    (object) [
                        'trx_id' => 1,
                        'web_id' => 2,
                        'play_id' => 'test-play-id',
                        'bet_time' => '2020-01-01 00:00:00',
                        'ip_address' => '192.168.1.1',
                        'bet_amount' => 100.0,
                    ]
                ]
            ]);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 161,
                'odds' => 1,
                'odds_type' => 1
            ]);

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $result = $service->getRunningTransactions(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    public function test_getRunningTransactions_stubGetBetDetailSportTypeNotNumberGameAndMixParlay_expectedData()
    {
        $expectedData = (object) [
            'totalCount' => 1,
            'data' => [
                [
                    'id' => 1,
                    'bet_id' => 1,
                    'branch_id' => 2,
                    'play_id' => 'test-play-id',
                    'bet_time' => '2020-01-01 00:00:00',
                    'bet_ip' => '192.168.1.1',
                    'amount' => 100.0,
                    'game_type' => 'betTypeName',
                    'league' => '-',
                    'match' => 'Mix Parlay',
                    'bet_option' => '-',
                    'hdp' => '-',
                    'odds' => 1,
                    'odds_type' => 'Malay Odds',
                    'sports_type' => 'betTypeName',
                    'bet_choice' => '-',
                    'bet_type' => 'betTypeName',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'detail_link' => url('/') . '/sab/in/visual/' . 'test-encrypt',
                ]
            ]
        ];

        Crypt::shouldReceive('encryptString')
            ->andReturn('test-encrypt');

        $request = new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getAllRunningTransactions')
            ->willReturn((object) [
                'totalCount' => 1,
                'data' => [
                    (object) [
                        'trx_id' => 1,
                        'web_id' => 2,
                        'play_id' => 'test-play-id',
                        'bet_time' => '2020-01-01 00:00:00',
                        'ip_address' => '192.168.1.1',
                        'bet_amount' => 100.0,
                    ]
                ]
            ]);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'odds' => 1,
                'odds_type' => 1,
                'ParlayData' => 'true',
                'bettypename' => [
                    (object) [
                        'name' => 'betTypeName'
                    ]
                ],
            ]);

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $result = $service->getRunningTransactions(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    public function test_getRunningTransactions_stubGetBetDetailSportTypeNotNumberGame_expectedData()
    {
        $expectedData = (object) [
            'totalCount' => 1,
            'data' => [
                [
                    'id' => 1,
                    'bet_id' => 1,
                    'branch_id' => 2,
                    'play_id' => 'test-play-id',
                    'bet_time' => '2020-01-01 00:00:00',
                    'bet_ip' => '192.168.1.1',
                    'amount' => 100.0,
                    'game_type' => 'betTypeName',
                    'league' => 'leagueName',
                    'match' => 'home vs away',
                    'bet_option' => 'home',
                    'hdp' => '2',
                    'odds' => 1,
                    'odds_type' => 'Malay Odds',
                    'sports_type' => 'betTypeName',
                    'bet_choice' => 'home',
                    'bet_type' => 'betTypeName',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'detail_link' => url('/') . '/sab/in/visual/' . 'test-encrypt',
                ]
            ]
        ];

        Crypt::shouldReceive('encryptString')
            ->andReturn('test-encrypt');

        $request = new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getAllRunningTransactions')
            ->willReturn((object) [
                'totalCount' => 1,
                'data' => [
                    (object) [
                        'trx_id' => 1,
                        'web_id' => 2,
                        'play_id' => 'test-play-id',
                        'bet_time' => '2020-01-01 00:00:00',
                        'ip_address' => '192.168.1.1',
                        'bet_amount' => 100.0,
                    ]
                ]
            ]);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'odds' => 1,
                'odds_type' => 1,
                'ParlayData' => null,
                'hometeamname' => [
                    (object) [
                        'name' => 'home'
                    ]
                ],
                'awayteamname' => [
                    (object) [
                        'name' => 'away'
                    ]
                ],
                'bet_team' => 1,
                'bettypename' => [
                    (object) [
                        'name' => 'betTypeName'
                    ]
                ],
                'leaguename' => [
                    (object) [
                        'name' => 'leagueName'
                    ]
                ],
                'hdp' => '2'
            ]);

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $result = $service->getRunningTransactions(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    public function test_getRunningTransactions_stubGetBetDetailMultipleSportTypeNumberGame_expectedData()
    {
        $expectedData = (object) [
            'totalCount' => 2,
            'data' => [
                [
                    'id' => 1,
                    'bet_id' => 1,
                    'branch_id' => 2,
                    'play_id' => 'test-play-id',
                    'bet_time' => '2020-01-01 00:00:00',
                    'bet_ip' => '192.168.1.1',
                    'amount' => 100.0,
                    'game_type' => '-',
                    'league' => '-',
                    'match' => '-',
                    'bet_option' => '-',
                    'hdp' => '-',
                    'odds' => 1,
                    'odds_type' => 'Malay Odds',
                    'sports_type' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'detail_link' => url('/') . '/sab/in/visual/' . 'test-encrypt',
                ],
                [
                    'id' => 2,
                    'bet_id' => 2,
                    'branch_id' => 3,
                    'play_id' => 'test-play-id',
                    'bet_time' => '2020-01-01 00:00:00',
                    'bet_ip' => '192.168.1.1',
                    'amount' => 100.0,
                    'game_type' => '-',
                    'league' => '-',
                    'match' => '-',
                    'bet_option' => '-',
                    'hdp' => '-',
                    'odds' => 1,
                    'odds_type' => 'Malay Odds',
                    'sports_type' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'detail_link' => url('/') . '/sab/in/visual/' . 'test-encrypt',
                ]
            ]
        ];

        Crypt::shouldReceive('encryptString')
            ->andReturn('test-encrypt');

        $request = new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getAllRunningTransactions')
            ->willReturn((object) [
                'totalCount' => 2,
                'data' => [
                    (object) [
                        'trx_id' => 1,
                        'web_id' => 2,
                        'play_id' => 'test-play-id',
                        'bet_time' => '2020-01-01 00:00:00',
                        'ip_address' => '192.168.1.1',
                        'bet_amount' => 100.0,
                    ],
                    (object) [
                        'trx_id' => 2,
                        'web_id' => 3,
                        'play_id' => 'test-play-id',
                        'bet_time' => '2020-01-01 00:00:00',
                        'ip_address' => '192.168.1.1',
                        'bet_amount' => 100.0,
                    ]
                ]
            ]);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 161,
                'odds' => 1,
                'odds_type' => 1
            ]);

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $result = $service->getRunningTransactions(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    public function test_getRunningTransactions_stubGetBetDetailReturnException_expectedData()
    {
        $expectedData = (object) [
            'totalCount' => 1,
            'data' => [
                [
                    'id' => 1,
                    'bet_id' => 1,
                    'branch_id' => 2,
                    'play_id' => 'test-play-id',
                    'bet_time' => '2020-01-01 00:00:00',
                    'bet_ip' => '192.168.1.1',
                    'amount' => 100.0,
                    'game_type' => '-',
                    'league' => '-',
                    'match' => '-',
                    'bet_option' => '-',
                    'hdp' => '-',
                    'odds' => '-',
                    'odds_type' => '-',
                    'sports_type' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'detail_link' => url('/') . '/sab/in/visual/' . 'test-encrypt',
                ]
            ]
        ];

        Crypt::shouldReceive('encryptString')
            ->andReturn('test-encrypt');

        $request = new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getAllRunningTransactions')
            ->willReturn((object) [
                'totalCount' => 1,
                'data' => [
                    (object) [
                        'trx_id' => 1,
                        'web_id' => 2,
                        'play_id' => 'test-play-id',
                        'bet_time' => '2020-01-01 00:00:00',
                        'ip_address' => '192.168.1.1',
                        'bet_amount' => 100.0,
                    ]
                ]
            ]);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willThrowException(new \Exception());

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $result = $service->getRunningTransactions(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    public function test_getRunningTransactions_stubGetBetDetailMultipleWithExceptionSportTypeNumberGame_expectedData()
    {
        $expectedData = (object) [
            'totalCount' => 2,
            'data' => [
                [
                    'id' => 1,
                    'bet_id' => 1,
                    'branch_id' => 2,
                    'play_id' => 'test-play-id',
                    'bet_time' => '2020-01-01 00:00:00',
                    'bet_ip' => '192.168.1.1',
                    'amount' => 100.0,
                    'game_type' => '-',
                    'league' => '-',
                    'match' => '-',
                    'bet_option' => '-',
                    'hdp' => '-',
                    'odds' => '-',
                    'odds_type' => '-',
                    'sports_type' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'detail_link' => url('/') . '/sab/in/visual/' . 'test-encrypt',
                ],
                [
                    'id' => 2,
                    'bet_id' => 2,
                    'branch_id' => 3,
                    'play_id' => 'test-play-id',
                    'bet_time' => '2020-01-01 00:00:00',
                    'bet_ip' => '192.168.1.1',
                    'amount' => 100.0,
                    'game_type' => '-',
                    'league' => '-',
                    'match' => '-',
                    'bet_option' => '-',
                    'hdp' => '-',
                    'odds' => 1,
                    'odds_type' => 'Malay Odds',
                    'sports_type' => '-',
                    'bet_choice' => '-',
                    'bet_type' => '-',
                    'live_score' => '-',
                    'is_live' => '-',
                    'ft_score' => '-',
                    'is_first_half' => '-',
                    'ht_score' => '-',
                    'detail_link' => url('/') . '/sab/in/visual/' . 'test-encrypt',
                ]
            ]
        ];

        Crypt::shouldReceive('encryptString')
            ->andReturn('test-encrypt');

        $request = new Request([
            'currency' => 'IDR',
            'branchId' => 1,
            'start' => 0,
            'length' => 50
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getAllRunningTransactions')
            ->willReturn((object) [
                'totalCount' => 2,
                'data' => [
                    (object) [
                        'trx_id' => 1,
                        'web_id' => 2,
                        'play_id' => 'test-play-id',
                        'bet_time' => '2020-01-01 00:00:00',
                        'ip_address' => '192.168.1.1',
                        'bet_amount' => 100.0,
                    ],
                    (object) [
                        'trx_id' => 2,
                        'web_id' => 3,
                        'play_id' => 'test-play-id',
                        'bet_time' => '2020-01-01 00:00:00',
                        'ip_address' => '192.168.1.1',
                        'bet_amount' => 100.0,
                    ]
                ]
            ]);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturnCallback(function () use (&$counter) {
                $counter++;

                if ($counter === 1) {
                    throw new Exception('Something went wrong');
                }

                return (object) [
                    'sport_type' => 161,
                    'odds' => 1,
                    'odds_type' => 1
                ];
            });

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $result = $service->getRunningTransactions(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $result);
    }

    public function test_unsettle_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUsername')
            ->with(username: $request->message['txns'][0]['userId'])
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => "testPayoutOperationID-12345",
                'bet_amount' => 500.0,
                'payout_amount' => 1000.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->unsettle(request: $request);
    }

    public function test_unsettle_nullPlayer_PlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->unsettle(request: $request);
    }

    public function test_unsettle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
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
                'bet_id' => "testPayoutOperationID-12345",
                'bet_amount' => 500.0,
                'payout_amount' => 1000.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials,
        );

        $service->unsettle(request: $request);
    }

    public function test_unsettle_invalidVendorID_InvalidKeyException()
    {
        $this->expectException(InvalidKeyException::class);

        $request = new Request([
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => "testPayoutOperationID-12345",
                'bet_amount' => 500.0,
                'payout_amount' => 1000.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('valid-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->unsettle(request: $request);
    }

    public function test_unsettle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->message['txns'][0]['txId'])
            ->willReturn((object) [
                'bet_id' => "testPayoutOperationID-12345",
                'bet_amount' => 500.0,
                'payout_amount' => 1000.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->unsettle(request: $request);
    }

    public function test_unsettle_nullTransaction_TransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->unsettle(request: $request);
    }

    #[DataProvider('invalidStatusForUnsettle')]
    public function test_unsettle_invalidTransactionStatus_InvalidTransactionStatusException($status)
    {
        $this->expectException(InvalidTransactionStatusException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => "testBetOperationID-12345",
                'bet_amount' => 500.0,
                'payout_amount' => 0.0,
                'flag' => $status,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->unsettle(request: $request);
    }

    public static function invalidStatusForUnsettle()
    {
        return [
            ['waiting'],
            ['running'],
            ['unsettle'],
            ['cancelled'],
            ['bonus']
        ];
    }

    public function test_unsettle_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => "testPayoutOperationID-12345",
            'bet_amount' => 500.0,
            'payout_amount' => 1000.0,
            'flag' => 'settled',
            'ip_address' => '123.456.7.8'
        ];

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationID-12345',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: '12345',
                betAmount: 500.0,
                payoutAmount: 0,
                betDate: '2021-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'unsettled',
                sportsbookDetails: new SabSettledSportsbookDetails(settledTransactionDetails: $transactionDetails)
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->unsettle(request: $request);
    }

    #[DataProvider('unsettleParams')]
    public function test_unsettle_mockWallet_resettle($flag, $walletMethod, $operationID)
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => $operationID,
                'bet_amount' => 500.0,
                'payout_amount' => 1000.0,
                'flag' => $flag,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('resettle')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: "resettle-testOperationID-12345",
                amount: -1000.0,
                betID: '12345',
                settledTransactionID: "{$walletMethod}-{$operationID}",
                betTime: '2021-01-01 12:00:00'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials,
        );

        $service->unsettle(request: $request);
    }

    public static function unsettleParams()
    {
        return [
            ['settled', 'payout', 'testPayoutOperationID'],
            ['resettled', 'resettle', 'testResettleOperationID']
        ];
    }

    public function test_unsettle_invalidWalletResettleResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00'
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => "testPayoutOperationID-12345",
                'bet_amount' => 500.0,
                'payout_amount' => 1000.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 'invalid',
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->unsettle(request: $request);
    }

    public function test_resettle_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUsername')
            ->with(username: $request->message['txns'][0]['userId'])
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'testPayoutOperationID-12345',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 3000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->resettle(request: $request);
    }

    public function test_resettle_nullPlayer_PlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->resettle(request: $request);
    }

    public function test_resettle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'testPayoutOperationID-12345',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 3000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials,
        );

        $service->resettle(request: $request);
    }

    public function test_resettle_invalidVendorID_InvalidKeyException()
    {
        $this->expectException(InvalidKeyException::class);

        $request = new Request([
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'testPayoutOperationID-12345',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('valid-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials
        );

        $service->resettle(request: $request);
    }

    public function test_resettle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'refId' => 'testTransactionID',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->message['txns'][0]['txId'])
            ->willReturn((object) [
                'bet_id' => 'testPayoutOperationID-12345',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 3000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->resettle(request: $request);
    }

    public function test_resettle_nullTransaction_TransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
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
            ->willReturn(null);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->resettle(request: $request);
    }

    #[DataProvider('invalidStatusForResettle')]
    public function test_resettle_invalidTransactionStatus_InvalidTransactionStatusException($status)
    {
        $this->expectException(InvalidTransactionStatusException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'status' => 'win',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'testBetOperationID-12345',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'flag' => $status,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
        );

        $service->resettle(request: $request);
    }

    public static function invalidStatusForResettle()
    {
        return [
            ['waiting'],
            ['running'],
            ['unsettle'],
            ['cancelled'],
            ['bonus']
        ];
    }

    public function test_resettle_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => 'testPayoutOperationID-12345',
            'bet_amount' => 1000.0,
            'payout_amount' => 0.0,
            'flag' => 'settled',
            'ip_address' => '123.456.7.8'
        ];

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationID-12345',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: '12345',
                betAmount: 1000.0,
                payoutAmount: 3000.0,
                betDate: '2021-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'resettled',
                sportsbookDetails: new SabSettledSportsbookDetails(settledTransactionDetails: $transactionDetails)
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 3000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->resettle(request: $request);
    }

    #[DataProvider('validSettledFlagsAndSettledTransactionID')]
    public function test_resettle_mockWallet_resettle($flag, $settledTransactionID)
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'testPayoutOperationID-12345',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'flag' => $flag,
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('resettle')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'resettle-testOperationID-12345',
                amount: 3000.0,
                betID: '12345',
                settledTransactionID: $settledTransactionID,
                betTime: '2021-01-01 12:00:00'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 3000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials,
        );

        $service->resettle(request: $request);
    }

    public static function validSettledFlagsAndSettledTransactionID()
    {
        return [
            ['settled', 'payout-testPayoutOperationID-12345'],
            ['resettled', 'resettle-testPayoutOperationID-12345'],
        ];
    }

    public function test_resettle_invalidWalletResettleResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 3,
                        'txId' => 12345
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'testPayoutOperationID-12345',
                'bet_amount' => 1000.0,
                'payout_amount' => 0.0,
                'flag' => 'settled',
                'ip_address' => '123.456.7.8'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 'invalid',
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
        );

        $service->resettle(request: $request);
    }

    public function test_adjustBalance_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');

        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUsername')
            ->with(username: 'testUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('TransferIn')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(repository: $mockRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->adjustBalance(request: $request);
    }

    public function test_adjustBalance_nullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->adjustBalance(request: $request);
    }

    public function test_adjustBalance_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');

        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('TransferIn')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, wallet: $stubWallet);
        $service->adjustBalance(request: $request);
    }

    public function test_adjustBalance_invalidVendorID_invalidKeyException()
    {
        $this->expectException(InvalidKeyException::class);

        $request = new Request([
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');

        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->adjustBalance(request: $request);
    }

    public function test_adjustBalance_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');

        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: 12345);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('TransferIn')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(repository: $mockRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->adjustBalance(request: $request);
    }

    public function test_adjustBalance_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');

        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: "testOperationId-12345",
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: 12345,
                betAmount: 0,
                payoutAmount: 1000,
                betDate: '2020-01-02 00:00:00',
                ip: null,
                flag: 'bonus',
                sportsbookDetails: new SabRunningSportsbookDetails(gameCode: 17003)
            );

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('TransferIn')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(repository: $mockRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->adjustBalance(request: $request);
    }

    public function test_adjustBalance_mockWallet_transferIn()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');

        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('TransferIn')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: "bonus-12345",
                amount: 1000,
                betTime: '2020-01-02 00:00:00'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
        $service->adjustBalance(request: $request);
    }

    public function test_adjustBalance_invalidWalletTransferInResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 1,
                    'debitAmount' => 0.0,
                ]
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');

        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('TransferIn')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->adjustBalance(request: $request);
    }

    public function test_adjustBalance_mockWallet_transferOut()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 1,
                ]
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');

        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('TransferOut')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: "bonus-12345",
                amount: 1000,
                betTime: '2020-01-02 00:00:00'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
        $service->adjustBalance(request: $request);
    }

    public function test_adjustBalance_invalidWalletTransferOutResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationId',
                'userId' => 'testUsername',
                'refId' => 'testTransactionID',
                'txId' => 12345,
                'time' => '2020-01-01 12:00:00',
                'betType' => 17003,
                'balanceInfo' => [
                    'creditAmount' => 0.0,
                    'debitAmount' => 1,
                ]
            ]
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');

        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('TransferOut')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->adjustBalance(request: $request);
    }

    public function test_settle_mockRepository_getPlayerByUsername()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByUsername')
            ->with(username: $request->message['txns'][0]['userId'])
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'bet_amount' => 1000.0,
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_nullPlayer_PlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'bet_amount' => 1000.0,
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $mockCredentials = $this->createMock(SabCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $mockCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_invalidVendorID_InvalidKeyException()
    {
        $this->expectException(InvalidKeyException::class);

        $request = new Request([
            'key' => 'invalid-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'bet_amount' => 1000.0,
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('valid-vendor-id');

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
            ->with(transactionID: $request->message['txns'][0]['txId'])
            ->willReturn((object) [
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'bet_amount' => 1000.0,
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_nullTransaction_TransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials
        );

        $service->settle(request: $request);
    }

    #[DataProvider('invalidStatusForSettle')]
    public function test_settle_invalidTransactionStatus_invalidTransactionStatusException($status)
    {
        $this->expectException(InvalidTransactionStatusException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
                'bet_id' => 'testAllOperationID-12345',
                'trx_id' => '12345',
                'bet_amount' => 1000.0,
                'flag' => $status,
                'ip_address' => '123.456.7.8',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public static function invalidStatusForSettle()
    {
        return [
            ['waiting'],
            ['settled'],
            ['resettled'],
            ['cancelled'],
            ['bonus']
        ];
    }

    public function test_settle_mockApi_getBetDetail()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'bet_amount' => 1000.0,
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(SabApi::class);
        $mockApi->expects($this->once())
            ->method('getBetDetail')
            ->with(credentials: $providerCredentials, transactionID: '12345')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            api: $mockApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_getBetDetailThrowsThirdPartyError_ProviderThirdPartyApiErrorException()
    {
        $this->expectException(ProviderThirdPartyApiErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
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
                'bet_id' => 'testBetOperationID-12345',
                'trx_id' => '12345',
                'bet_amount' => 1000.0,
                'flag' => 'running',
                'ip_address' => '123.456.7.8',
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willThrowException(new ThirdPartyApiErrorException);

        $service = $this->makeService(
            repository: $stubRepository,
            api: $stubApi,
            credentials: $stubCredentials,
        );

        $service->settle(request: $request);
    }

    #[DataProvider('numberGameParams')]
    public function test_settle_mockRepository_SabNumberGameSportsbookDetails($param)
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => 'testBetOperationID-12345',
            'trx_id' => '12345',
            'bet_amount' => 1000.0,
            'flag' => 'running',
            'ip_address' => '123.456.7.8',
        ];

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationID-12345',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: '12345',
                betAmount: 1000.0,
                payoutAmount: 2000.0,
                betDate: '2021-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'settled',
                sportsbookDetails: new SabNumberGameSportsbookDetails(
                    sabSportsbookDetails: (object) [
                        'sport_type' => $param
                    ],
                    ipAddress: '123.456.7.8'
                )
            );

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => $param
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public static function numberGameParams()
    {
        return [
            [161],
            [164]
        ];
    }

    public function test_settle_mockRepository_SabSportsbookDetails()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => 'testBetOperationID-12345',
            'trx_id' => '12345',
            'bet_amount' => 1000.0,
            'flag' => 'running',
            'ip_address' => '123.456.7.8',
        ];

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationID-12345',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: '12345',
                betAmount: 1000.0,
                payoutAmount: 2000.0,
                betDate: '2021-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'settled',
                sportsbookDetails: new SabSportsbookDetails(
                    sabSportsbookDetails: (object) [
                        'sport_type' => 1,
                        'ParlayData' => null
                    ],
                    ipAddress: '123.456.7.8'
                )
            );

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_SabMixParlaySportsbookDetails()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => 'testBetOperationID-12345',
            'trx_id' => '12345',
            'bet_amount' => 1000.0,
            'flag' => 'running',
            'ip_address' => '123.456.7.8',
        ];

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationID-12345',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: '12345',
                betAmount: 1000.0,
                payoutAmount: 2000.0,
                betDate: '2021-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'settled',
                sportsbookDetails: new SabMixParlaySportsbookDetails(
                    sabSportsbookDetails: (object) [
                        'sport_type' => 1,
                        'ParlayData' => (object) []
                    ],
                    ipAddress: '123.456.7.8'
                )
            );

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => (object) []
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_createTransaction()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => 'testBetOperationID-12345',
            'trx_id' => '12345',
            'bet_amount' => 1000.0,
            'flag' => 'running',
            'ip_address' => '123.456.7.8',
        ];

        $mockRepository = $this->createMock(SabRepository::class);
        $mockRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                betID: 'testOperationID-12345',
                playID: 'testPlayID',
                currency: 'IDR',
                trxID: '12345',
                betAmount: 1000.0,
                payoutAmount: 2000.0,
                betDate: '2021-01-01 12:00:00',
                ip: '123.456.7.8',
                flag: 'settled',
                sportsbookDetails: new SabSportsbookDetails(
                    sabSportsbookDetails: (object) [
                        'sport_type' => 1,
                        'ParlayData' => null
                    ],
                    ipAddress: '123.456.7.8'
                )
            );

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null,
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_resettle()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => 'testPayoutOperationID-12345',
            'trx_id' => '12345',
            'bet_amount' => 1000.0,
            'flag' => 'unsettled',
            'ip_address' => '123.456.7.8',
        ];

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null,
            ]);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('resettle')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: 'resettle-testOperationID-12345',
                amount: 2000.0,
                betID: '12345',
                settledTransactionID: 'resettle-testPayoutOperationID-12345',
                betTime: '2021-01-01 12:00:00'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            api: $stubApi,
            credentials: $stubCredentials,
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockReport_makeSportsbookReport()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => 'testBetOperationID-12345',
            'trx_id' => '12345',
            'bet_amount' => 1000.0,
            'flag' => 'running',
            'ip_address' => '123.456.7.8',
        ];

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null,
            ]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSportsbookReport')
            ->with(
                trxID: '12345',
                betTime: '2021-01-01 12:00:00',
                sportsbookDetails: new SabSportsbookDetails(
                    sabSportsbookDetails: (object) [
                        'sport_type' => 1,
                        'ParlayData' => null
                    ],
                    ipAddress: '123.456.7.8'
                )
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $mockReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_payout()
    {
        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => 'testBetOperationID-12345',
            'trx_id' => '12345',
            'bet_amount' => 1000.0,
            'flag' => 'running',
            'ip_address' => '123.456.7.8',
        ];

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null,
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
                transactionID: "payout-testOperationID-12345",
                amount: 2000.0,
                report: new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_invalidWalletResettleResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => "testPayoutOperationID-12345",
            'trx_id' => '12345',
            'bet_amount' => 1000.0,
            'flag' => 'unsettled',
            'ip_address' => '123.456.7.8',
        ];

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null,
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 'invalid',
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
        );

        $service->settle(request: $request);
    }

    public function test_settle_invalidWalletPayoutResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'key' => 'test-vendor-id',
            'message' => [
                'operationId' => 'testOperationID',
                'txns' => [
                    [
                        'userId' => 'testUsername',
                        'txId' => 12345,
                        'updateTime' => '2021-01-01T00:00:00.000-04:00',
                        'payout' => 2
                    ]
                ]
            ]
        ]);

        $transactionDetails = (object) [
            'bet_id' => "testBetOperationID-12345",
            'trx_id' => '12345',
            'bet_amount' => 1000.0,
            'flag' => 'running',
            'ip_address' => '123.456.7.8',
        ];

        $stubRepository = $this->createMock(SabRepository::class);
        $stubRepository->method('getPlayerByUsername')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($transactionDetails);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getVendorID')
            ->willReturn('test-vendor-id');
        $providerCredentials->method('getCurrencyConversion')
            ->willReturn(1000);

        $stubCredentials = $this->createMock(SabCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(SabApi::class);
        $stubApi->method('getBetDetail')
            ->willReturn((object) [
                'sport_type' => 1,
                'ParlayData' => null,
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSportsbookReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 'invalid'
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            api: $stubApi,
            credentials: $stubCredentials,
            walletReport: $stubReport
        );

        $service->settle(request: $request);
    }
}
