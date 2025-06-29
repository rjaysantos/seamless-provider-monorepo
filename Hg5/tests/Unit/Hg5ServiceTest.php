<?php

use Carbon\Carbon;
use Tests\TestCase;
use Providers\Hg5\Hg5Api;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Hg5\Hg5Service;
use Providers\Hg5\Hg5Repository;
use Providers\Hg5\Hg5Credentials;
use Providers\Hg5\DTO\Hg5PlayerDTO;
use Providers\Hg5\DTO\Hg5RequestDTO;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Hg5\Contracts\ICredentials;
use App\Exceptions\Casino\PlayerNotFoundException;
use Providers\Hg5\Exceptions\WalletErrorException;
use Providers\Hg5\Exceptions\GameNotFoundException;
use Providers\Hg5\Exceptions\InvalidTokenException;
use Providers\Hg5\Exceptions\InvalidAgentIDException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Hg5\Exceptions\InsufficientFundException;
use Providers\Hg5\Exceptions\TransactionAlreadyExistsException;
use Providers\Hg5\Exceptions\TransactionAlreadySettledException;
use Providers\Hg5\Exceptions\WalletErrorException as ProviderWalletErrorException;
use Providers\Hg5\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use Providers\Hg5\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class Hg5ServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $wallet = null,
        $walletReport = null
    ): Hg5Service {
        $repository ??= $this->createStub(Hg5Repository::class);
        $credentials ??= $this->createStub(Hg5Credentials::class);
        $api ??= $this->createStub(Hg5Api::class);
        $wallet ??= $this->createStub(IWallet::class);
        $walletReport ??= $this->createStub(WalletReport::class);

        return new Hg5Service(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            wallet: $wallet,
            walletReport: $walletReport
        );
    }

    public function test_getLaunchUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
        ]);

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameLink')
            ->willReturn((object) [
                'url' => 'testLaunchUrl.com',
                'token' => 'testToken'
            ]);

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
        ]);

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(
                playID: 'testPlayID',
                username: 'testUsername',
                currency: 'IDR'
            );

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameLink')
            ->willReturn((object) [
                'url' => 'testLaunchUrl.com',
                'token' => 'testToken'
            ]);

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
        ]);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameLink')
            ->willReturn((object) [
                'url' => 'testLaunchUrl.com',
                'token' => 'testToken'
            ]);

        $service = $this->makeService(credentials: $mockCredentials, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_getGameLink()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
        ]);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getApiUrl')
            ->willReturn('testApiUrl.com');

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(Hg5Api::class);
        $mockApi->expects($this->once())
            ->method('getGameLink')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID',
                gameCode: 'testGameID'
            )
            ->willReturn((object) [
                'url' => 'testLaunchUrl.com',
                'token' => 'testToken'
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            api: $mockApi
        );

        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createOrUpdatePlayGame()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
        ]);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameLink')
            ->willReturn((object) [
                'url' => 'testLaunchUrl.com',
                'token' => 'testToken'
            ]);

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('createOrUpdatePlayGame')
            ->with(
                playID: $request->playId,
                token: 'testToken'
            );

        $service = $this->makeService(api: $stubApi, repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expectedData()
    {
        $expected = 'testLaunchUrl.com';

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID'
        ]);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameLink')
            ->willReturn((object) [
                'url' => 'testLaunchUrl.com',
                'token' => 'testToken'
            ]);

        $service = $this->makeService(api: $stubApi);
        $result = $service->getLaunchUrl(request: $request);

        $this->assertEquals(expected: $expected, actual: $result);
    }

    public function test_getBetDetailUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->play_id)
            ->willReturn((object) []);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testBetID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderQuery')
            ->willReturn(collect([(object) ['round' => 'testBetID']]));

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepositoryNullPlayer_PlayerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->bet_id)
            ->willReturn((object) [
                'trx_id' => 'testBetID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderQuery')
            ->willReturn(collect([(object) ['round' => 'testBetID']]));

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepositoryNullTransaction_TransactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testBetID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderQuery')
            ->willReturn(collect([(object) ['round' => 'testBetID']]));

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, api: $stubApi);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockApi_getOrderDetailLink()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testBetID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(Hg5Api::class);
        $mockApi->method('getOrderQuery')
            ->willReturn(collect([(object) ['round' => 'testBetID']]));

        $mockApi->expects($this->once())
            ->method('getOrderDetailLink')
            ->with(
                credentials: $providerCredentials,
                transactionID: $request->bet_id,
                playID: $request->play_id
            );

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, api: $mockApi);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubApi_expectedData()
    {
        $expectedData = 'testVisualUrl.com';

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testBetID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderQuery')
            ->willReturn(collect([(object) ['round' => 'testBetID']]));

        $stubApi->method('getOrderDetailLink')
            ->willReturn('testVisualUrl.com');

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, api: $stubApi);
        $response = $service->getBetDetailUrl(request: $request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_getBetDetailUrl_stubApiFishGame_expectedData()
    {
        Crypt::shouldReceive('encryptString')->andReturn('encryptedPlayID', 'encryptedBetID');

        // Expected URL Return (different in variable due to how request()->getFullUrl() provides data)
        // https://localhost/hg5/in/visual/encryptedPlayID/encryptedBetID
        $expectedData = 'http://localhost/encryptedPlayID/encryptedBetID';

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'testBetID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderQuery')
            ->willReturn(collect([(object) ['gameroundid' => 'testBetID']]));

        $stubApi->method('getOrderDetailLink')
            ->willReturn('testVisualUrl.com');

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, api: $stubApi);
        $response = $service->getBetDetailUrl(request: $request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_getBetDetailData_mockRepository_getPlayerByPlayID()
    {
        $decryptedPlayID = 'testPlayID';
        $decryptedTrxID = 'hg5-testTransactionID';

        Crypt::shouldReceive('decryptString')->andReturn($decryptedPlayID, $decryptedTrxID);

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $decryptedPlayID)
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'hg5-testTransactionID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderQuery')
            ->willReturn(collect([
                (object) [
                    'gameroundid' => 'testTransactionID',
                    'round' => 'testRound1',
                    'bet' => 100,
                    'win' => 200,
                ]
            ]));

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetailData(encryptedPlayID: 'testPlayID', encryptedTrxID: 'hg5-testTransactionID');
    }

    public function test_getBetDetailData_stubRepositoryNullPlayer_PlayerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $decryptedPlayID = 'testPlayID';
        $decryptedTrxID = 'hg5-testTransactionID';

        Crypt::shouldReceive('decryptString')->andReturn($decryptedPlayID, $decryptedTrxID);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailData(encryptedPlayID: 'testPlayID', encryptedTrxID: 'hg5-testTransactionID');
    }

    public function test_getBetDetailData_mockRepository_getTransactionByTrxID()
    {
        $decryptedPlayID = 'testPlayID';
        $decryptedTrxID = 'hg5-testTransactionID';

        Crypt::shouldReceive('decryptString')->andReturn($decryptedPlayID, $decryptedTrxID);

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(roundID: $decryptedTrxID)
            ->willReturn((object) [
                'trx_id' => 'hg5-testTransactionID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderQuery')
            ->willReturn(collect([
                (object) [
                    'gameroundid' => 'testTransactionID',
                    'round' => 'testRound1',
                    'bet' => 100,
                    'win' => 200,
                ]
            ]));

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetailData(encryptedPlayID: 'testPlayID', encryptedTrxID: 'hg5-testTransactionID');
    }

    public function test_getBetDetailData_stubRepositoryNullTransaction_TransactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $decryptedPlayID = 'testPlayID';
        $decryptedTrxID = 'hg5-testTransactionID';

        Crypt::shouldReceive('decryptString')->andReturn($decryptedPlayID, $decryptedTrxID);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailData(encryptedPlayID: 'testPlayID', encryptedTrxID: 'hg5-testTransactionID');
    }

    public function test_getBetDetailData_mockCredentials_getCredentialsByCurrency()
    {
        $decryptedPlayID = 'testPlayID';
        $decryptedTrxID = 'hg5-testTransactionID';

        Crypt::shouldReceive('decryptString')->andReturn($decryptedPlayID, $decryptedTrxID);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'hg5-testTransactionID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderQuery')
            ->willReturn(collect([
                (object) [
                    'gameroundid' => 'testTransactionID',
                    'round' => 'testRound1',
                    'bet' => 100,
                    'win' => 200,
                ]
            ]));

        $service = $this->makeService(repository: $stubRepository, api: $stubApi, credentials: $mockCredentials);
        $service->getBetDetailData(encryptedPlayID: 'testPlayID', encryptedTrxID: 'hg5-testTransactionID');
    }

    public function test_getBetDetailData_mockApi_getOrderQuery()
    {
        $decryptedPlayID = 'testPlayID';
        $decryptedTrxID = 'hg5-testTransactionID';

        Crypt::shouldReceive('decryptString')->andReturn($decryptedPlayID, $decryptedTrxID);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'hg5-testTransactionID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockApi = $this->createMock(Hg5Api::class);
        $mockApi->expects($this->once())
            ->method('getOrderQuery')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID',
                startDate: '2024-01-01 00:00:00',
                endDate: '2024-01-01 00:00:00'
            )
            ->willReturn(collect([
                (object) [
                    'gameroundid' => 'testTransactionID',
                    'round' => 'testRound1',
                    'bet' => 100,
                    'win' => 200,
                ]
            ]));

        $service = $this->makeService(repository: $stubRepository, api: $mockApi, credentials: $stubCredentials);
        $service->getBetDetailData(encryptedPlayID: 'testPlayID', encryptedTrxID: 'hg5-testTransactionID');
    }

    public function test_getBetDetailData_stubApi_expectedData()
    {
        $expectedData = [
            'playID' => 'testPlayID',
            'currency' => 'IDR',
            'trxID' => 'hg5-testTransactionID',
            'roundData' => [
                [
                    'roundID' => 'testRound1',
                    'bet' => 100,
                    'win' => 200
                ]
            ]
        ];

        $decryptedPlayID = 'testPlayID';
        $decryptedTrxID = 'hg5-testTransactionID';

        Crypt::shouldReceive('decryptString')->andReturn($decryptedPlayID, $decryptedTrxID);

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => 'hg5-testTransactionID',
                'updated_at' => '2024-01-01 00:00:00',
                'created_at' => '2024-01-01 00:00:00'
            ]);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderQuery')
            ->willReturn(collect([
                (object) [
                    'gameroundid' => 'testTransactionID',
                    'round' => 'testRound1',
                    'bet' => 100,
                    'win' => 200
                ]
            ]));

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $response = $service->getBetDetailData(encryptedPlayID: 'testPlayID', encryptedTrxID: 'hg5-testTransactionID');

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_getFishGameDetailUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playID' => 'testPlayID',
            'currency' => 'IDR',
            'trxID' => 'testTransactionID'
        ]);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $service = $this->makeService(credentials: $mockCredentials);
        $service->getFishGameDetailUrl(request: $request);
    }

    public function test_getFishGameDetailUrl_mockApi_getOrderDetailLink()
    {
        $request = new Request([
            'playID' => 'testPlayID',
            'currency' => 'IDR',
            'trxID' => 'testTransactionID'
        ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockApi = $this->createMock(Hg5Api::class);
        $mockApi->expects($this->once())
            ->method('getOrderDetailLink')
            ->with(
                credentials: $stubProviderCredentials,
                transactionID: $request->trxID,
                playID: $request->playID
            );

        $service = $this->makeService(credentials: $stubCredentials, api: $mockApi);
        $service->getFishGameDetailUrl(request: $request);
    }

    public function test_getFishGameDetailUrl_stubApi_expectedData()
    {
        $expectedData = 'testFishGameVisualUrl.com';

        $request = new Request([
            'playID' => 'testPlayID',
            'currency' => 'IDR',
            'trxID' => 'testTransactionID'
        ]);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getOrderDetailLink')
            ->willReturn('testFishGameVisualUrl.com');

        $service = $this->makeService(api: $stubApi);
        $response = $service->getFishGameDetailUrl(request: $request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_getBalance_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playerId)
            ->willReturn((object) ['currency' => 'IDR']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $stubProviderCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $stubProviderCredentials->method('getAgentID')->willReturn(111);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWallet
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubRequestInvalidHeader_InvalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);
        $request->headers->set('Authorization', 'invalidToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getAuthorizationToken')->willReturn('validToken');

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubRequestInvalidAgent_InvalidAgentIDException()
    {
        $this->expectException(InvalidAgentIDException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 12451035534
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $stubProviderCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockWallet_balance()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $stubProviderCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->playerId
            )
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWalletStatusError_ProviderWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $stubProviderCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 657424]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWallet_expectedData()
    {
        $expectedData = (object) [
            'balance' => 1000,
            'currency' => 'IDR'
        ];

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $stubProviderCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );
        $response = $service->getBalance(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_authenticate_mockWallet_balance()
    {
        $requestDTO = new Hg5RequestDTO(
            auth: 'testAuthToken',
            playID: 'testPlayID',
            agentID: 111,
            token: 'testToken'
        );


        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn(new Hg5PlayerDTO(
                playID: 'testPlayID',
                username: 'testUsername',
                currency: 'IDR',
                token: 'testToken'
            ));

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getAuthorizationToken')->willReturn('testAuthToken');
        $stubProviderCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID'
            )
            ->willReturn([
                'credit' => 1000,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet
        );
        $service->authenticate(requestDTO: $requestDTO);
    }

    public function test_betAndSettle_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playerId)
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency)
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $mockCredentials,
            walletReport: $stubWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRequestInvalidHeader_InvalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'invalidToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRequestInvalidAgent_InvalidAgentIDException()
    {
        $this->expectException(InvalidAgentIDException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 6648864486,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockRepositoryExtraParameter_getTransactionByTrxID()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound2',
            'eventTime' => '2024-01-01T00:00:00-04:00',
            'extra' => [
                'slot' => [
                    'stage' => 'fg',
                    'mainBet' => '100',
                    'mainGameRound' => 'testGameRound1'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->exactly(2))
            ->method('getTransactionByTrxID')
            ->willReturnMap([
                [$request->extra['slot']['mainGameRound'], (object) ['trx_id' => 'testGameRound']],
                [$request->gameRound, null],
            ]);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRepositoryFirstGameRoundNotExist_ProviderTransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound2',
            'eventTime' => '2024-01-01T00:00:00-04:00',
            'extra' => [
                'slot' => [
                    'stage' => 'fg',
                    'mainBet' => '100',
                    'mainGameRound' => 'testGameRound1'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubRepositoryWithTransactionData_TransactionAlreadyExistsException()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00',
            'extra' => [
                'slot' => [
                    'stage' => 'fg',
                    'mainBet' => '100',
                    'mainGameRound' => 'testGameRound1'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testGameRound2']);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWallet_balance()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $providerCredentials,
                playID: $request->playerId
            )
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletBalanceInvalidStatus_ProviderWalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 3155641153]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWallet_InsufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 10.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockRepository_createWagerAndPayoutTransaction()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockRepository->expects($this->once())
            ->method('createWagerAndPayoutTransaction')
            ->with(
                trxID: $request->gameRound,
                betAmount: $request->withdrawAmount,
                winAmount: $request->depositAmount,
                transactionDate: '2024-01-01 12:00:00'
            );

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockApi_getGameList()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(Hg5Api::class);
        $mockApi->expects($this->once())
            ->method('getGameList')
            ->with(credentials: $providerCredentials)
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            api: $mockApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubApiInvalidGame_GameNotFoundException()
    {
        $this->expectException(GameNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'invalidGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWalletReport_makeSlotReport()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: '8a298c9fac9e4321b1ebe27a6df83f5c', // $request->gameRound
                gameCode: $request->gameCode,
                betTime: '2024-01-01 12:00:00',
                opt: json_encode(['txn_id' => $request->gameRound])
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $mockWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWalletReport_makeArcadeReport()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'arcade',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: '8a298c9fac9e4321b1ebe27a6df83f5c', // $request->gameRound
                gameCode: $request->gameCode,
                betTime: '2024-01-01 12:00:00',
                opt: json_encode(['txn_id' => $request->gameRound])
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $mockWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_mockWallet_wagerAndPayout()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockWallet->expects($this->once())
            ->method('wagerAndPayout')
            ->with(
                credentials: $providerCredentials,
                playID: $request->playerId,
                currency: $request->currency,
                wagerTransactionID: "wager-{$request->gameRound}",
                wagerAmount: $request->withdrawAmount,
                payoutTransactionID: "payout-{$request->gameRound}",
                payoutAmount: $request->depositAmount,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWalletWagerAndPayoutInvalidStatus_ProviderWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn(['status_code' => 40684513483]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            api: $stubApi
        );
        $service->betAndSettle(request: $request);
    }

    public function test_betAndSettle_stubWallet_expectedData()
    {
        $expected = 1200.00;

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'withdrawAmount' => 100,
            'depositAmount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubApi = $this->createMock(Hg5Api::class);
        $stubApi->method('getGameList')
            ->willReturn(collect([
                (object) [
                    'gametype' => 'slot',
                    'gamecode' => 'testGameCode'
                ]
            ]));

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            api: $stubApi
        );
        $response = $service->betAndSettle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_bet_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playerId)
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency)
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubRequestInvalidHeader_InvalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'invalidToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->bet(request: $request);
    }

    public function test_bet_stubRequestInvalidAgent_InvalidAgentIDException()
    {
        $this->expectException(InvalidAgentIDException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 1421453513,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->gameRound);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubRepositoryTransactionDataExists_TransactionAlreadyExists()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testGameRound1']);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_balance()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: $request->playerId)
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet->method('wager')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWalletBalanceInvalidStatus_ProviderWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn(['status_code' => 153648153]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
        $service->bet(request: $request);
    }

    public function test_bet_stubWalletLowBalance_InsufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_createBetTransaction()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockRepository->expects($this->once())
            ->method('createBetTransaction')
            ->with(
                trxID: $request->gameRound,
                betAmount: $request->amount,
                transactionDate: '2024-01-01 12:00:00'
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockWalletReport_makeArcadeReport()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: 'a242ef935b602ef8d3fe2abe4802d509', // $request->gameRound
                gameCode: $request->gameCode,
                betTime: '2024-01-01 12:00:00',
                opt: json_encode(['txn_id' => $request->gameRound])
            )
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $mockWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_wager()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $providerCredentials,
                playID: $request->playerId,
                currency: $request->currency,
                transactionID: "wager-{$request->gameRound}",
                amount: $request->amount,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWalletWagerInvalidStatus_ProviderWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn(['status_code' => 5648631538]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->bet(request: $request);
    }

    public function test_settle_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playerId)
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_stubRequestInvalidHeader_InvalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'invalidToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->settle(request: $request);
    }

    public function test_settle_stubRequestInvalidAgent_InvalidAgentIDException()
    {
        $this->expectException(InvalidAgentIDException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 15151351351,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->gameRound)
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubRepositoryNullTransaction_ProviderTransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->settle(request: $request);
    }

    public function test_settle_stubRepositoryTransactionAlreadySettled_TransactionAlreadySettledException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => '2024-01-01 00:00:00']);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_createSettleTransaction()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockRepository->expects($this->once())
            ->method('settleTransaction')
            ->with(
                trxID: $request->gameRound,
                winAmount: $request->amount,
                settleTime: '2024-01-01 12:00:00'
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockWalletReport_makeArcadeReport()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: 'a242ef935b602ef8d3fe2abe4802d509',
                gameCode: $request->gameCode,
                betTime: '2024-01-01 12:00:00',
                opt: json_encode(['txn_id' => $request->gameRound])
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $mockWalletReport
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_payout()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                credentials: $providerCredentials,
                playID: $request->playerId,
                currency: $request->currency,
                transactionID: "payout-{$request->gameRound}",
                amount: $request->amount,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubWalletPayoutInvalidStatus_ProviderWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn(['status_code' => 3534543]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->settle(request: $request);
    }

    public function test_settle_stubWallet_expectedData()
    {
        $expectedData = 1000.00;

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'amount' => 300,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->settle(request: $request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_multipleBet_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->datas[0]->playerId)
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 800.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multipleBet(request: $request);
    }

    public function test_multipleBet_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => '2',
                'message' => 'Player not found.',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $response = $service->multipleBet(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleBet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 800.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multipleBet(request: $request);
    }

    public function test_multipleBet_stubRequestInvalidHeader_InvalidTokenException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => 3,
                'message' => 'Token Invalid',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'invalidToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $response = $service->multipleBet(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleBet_stubRequestInvalidAgent_InvalidAgentIDException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => 31,
                'message' => "Currency does not match Agent's currency.",
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 5465486468,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 5465486468,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $response = $service->multipleBet(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleBet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: 'testGameRound1');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 800.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multipleBet(request: $request);
    }

    public function test_multipleBet_stubRepositoryDuplicateTrxID_TransactionAlreadyExistsException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => 103,
                'message' => 'Transaction service error',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testGameRound1']);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $response = $service->multipleBet(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleBet_mockWallet_balance()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID1'
            )
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet->method('wager')
            ->willReturn([
                'credit_after' => 800.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->multipleBet(request: $request);
    }

    public function test_multipleBet_stubWalletBalanceInvalidStatus_ProviderWalletErrorException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => '105',
                'message' => 'Wallet service error.',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 5444868834]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );
        $response = $service->multipleBet(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleBet_stubWalletInsufficientBalance_InsufficientFundException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => '1',
                'message' => 'Insufficient balance.',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet
        );
        $response = $service->multipleBet(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleBet_mockRepository_createBetTransaction()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockRepository->expects($this->once())
            ->method('createBetTransaction')
            ->with(
                trxID: 'testGameRound1',
                betAmount: 200,
                transactionDate: '2024-01-01 12:00:00'
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 800.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multipleBet(request: $request);
    }

    public function test_multipleBet_mockWalletReport_makeArcadeReport()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: 'a242ef935b602ef8d3fe2abe4802d509', // 'testGameRound1',
                gameCode: 'testGameCode',
                betTime: '2024-01-01 12:00:00',
                opt: json_encode(['txn_id' => 'testGameRound1'])
            )
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 800.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multipleBet(request: $request);
    }

    public function test_multipleBet_mockWallet_wager()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID1',
                currency: 'IDR',
                transactionID: 'wager-testGameRound1',
                amount: 200,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 800.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multipleBet(request: $request);
    }

    public function test_multipleBet_stubWalletWagerAndPayoutInvalidStatus_ProviderWalletErrorException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => '105',
                'message' => 'Wallet service error.',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn(['status_code' => 3484331583]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->multipleBet(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleBet_stubWallet_expectedData()
    {
        $expectedData = [
            (object) [
                'code' => '0',
                'message' => '',
                'balance' => 800.00,
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 800.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->multipleBet(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_mockRepository_getPlayerByPlayID()
    {
        $expectedData = [
            (object) [
                'code' => '0',
                'message' => '',
                'balance' => 1200.00,
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'testPlayID1')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => '2',
                'message' => 'Player not found.',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_mockCredentials_getCredentialsByCurrency()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => '0',
                'message' => '',
                'balance' => 1200.00,
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_stubRequestInvalidHeader_InvalidTokenException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => 3,
                'message' => 'Token Invalid',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'invalidToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_stubRequestInvalidAgent_InvalidAgentIDException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => 31,
                'message' => "Currency does not match Agent's currency.",
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 15335513,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 15335513,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_mockRepository_getTransactionByTrxID()
    {
        $expectedData = [
            (object) [
                'code' => '0',
                'message' => '',
                'balance' => 1200.00,
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: 'testGameRound1')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_stubRepositoryNullReport_ProviderTransactionNotFoundException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => 36,
                'message' => 'GameRound not existed.',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_stubRepositoryTransactionAlreadySettled_TransactionAlreadySettledException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => 103,
                'message' => 'Transaction service error',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => '2024-01-01 00:00:00']);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_mockRepository_createPayoutTransaction()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockRepository->expects($this->once())
            ->method('settleTransaction')
            ->with(
                trxID: 'testGameRound1',
                winAmount: 200.00,
                settleTime: '2024-01-01 12:00:00'
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multipleSettle(request: $request);
    }

    public function test_multipleSettle_mockWalletReport_makeArcadeReport()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: 'a242ef935b602ef8d3fe2abe4802d509',
                gameCode: 'testGameCode',
                betTime: '2024-01-01 12:00:00',
                opt: json_encode(['txn_id' => 'testGameRound1'])
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $mockWalletReport
        );
        $service->multipleSettle(request: $request);
    }

    public function test_multipleSettle_mockWallet_payout()
    {
        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID1',
                currency: 'IDR',
                transactionID: "payout-testGameRound1",
                amount: 200.00,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->multipleSettle(request: $request);
    }

    public function test_multipleSettle_stubWalletInvalidStatusCode_ProviderWalletErrorException()
    {
        Carbon::setTestNow('2024-01-01 12:00:00');

        $expectedData = [
            (object) [
                'code' => 105,
                'message' => 'Wallet service error.',
                'balance' => 0.00,
                'datetime' => '2024-01-01T00:00:00.000000000-04:00',
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn(['status_code' => 453153531]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multipleSettle_stubWallet_expectedData()
    {
        $expectedData = [
            (object) [
                'code' => '0',
                'message' => '',
                'balance' => 1200.00,
                'currency' => 'IDR',
                'playerId' => 'testPlayID1',
                'agentId' => 111,
                'gameRound' => 'testGameRound1'
            ]
        ];

        $request = new Request([
            'datas' => [
                (object) [
                    'playerId' => 'testPlayID1',
                    'agentId' => 111,
                    'amount' => 200.00,
                    'currency' => 'IDR',
                    'gameCode' => 'testGameCode',
                    'gameRound' => 'testGameRound1',
                    'eventTime' => '2024-01-01T00:00:00-04:00'
                ]
            ]
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->multipleSettle(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_multiplayerBet_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'testPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 500.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 500.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_stubRequestInvalidHeader_InvalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'invalidToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_stubRequestInvalidAgent_InvalidAgentIDException()
    {
        $this->expectException(InvalidAgentIDException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 44533553,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->gameRound);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 500.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_stubRepositoryTransactionExist_TransactionAlreadyExistsException()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testGameRound1']);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_mockWallet_balance()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $providerCredentials,
                playID: 'testPlayID'
            )
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockWallet->method('wager')
            ->willReturn([
                'credit_after' => 500.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_stubWalletBalanceFail_ProviderWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 3453434]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_stubWalletInsufficientBalance_InsufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_mockRepository_createBetTransaction()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockRepository->expects($this->once())
            ->method('createBetTransaction')
            ->with(
                trxID: $request->gameRound,
                betAmount: $request->amount,
                betTime: '2024-01-01 12:00:00'
            );

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 500.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_mockWalletReport_makeArcadeReport()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: 'a242ef935b602ef8d3fe2abe4802d509',
                gameCode: $request->gameCode,
                betTime: '2024-01-01 12:00:00',
                opt: json_encode(['txn_id' => $request->gameRound])
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 500.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $mockWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_mockWallet_wager()
    {
        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $providerCredentials,
                playID: $request->playerId,
                currency: $request->currency,
                transactionID: "wager-{$request->gameRound}",
                amount: $request->amount,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 500.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            walletReport: $stubWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_stubWalletWagerFail_ProviderWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn(['status_code' => 4534433]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $service->multiplayerBet(request: $request);
    }

    public function test_multiplayerBet_stubWallet_expectedData()
    {
        $expectedData = 500.00;

        $request = new Request([
            'playerId' => 'testPlayID',
            'agentId' => 111,
            'currency' => 'IDR',
            'amount' => 1000.00,
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 500.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            walletReport: $stubWalletReport
        );
        $response = $service->multiplayerBet(request: $request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_multiplayerSettle_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'testPlayID1')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_stubRepositoryNullPlayer_ProviderPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $mockCredentials = $this->createMock(Hg5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_stubRequestInvalidHeader_InvalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'invalidToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_stubRequestInvalidAgentID_InvalidAgentIDException()
    {
        $this->expectException(InvalidAgentIDException::class);

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 153513513,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: 'testGameRound1')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_stubRepositoryTransactionNotFound_ProviderTransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_stubRepositoryTransactionAlreadySettled_TransactionAlreadySettledException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => '2024-01-01 12:00:00']);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_mockRepository_settleTransaction()
    {
        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $mockRepository = $this->createMock(Hg5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockRepository->expects($this->once())
            ->method('settleTransaction')
            ->with(
                trxID: $request->gameRound,
                winAmount: $request->amount,
                settleTime: '2024-01-01 12:00:00'
            );

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_mockWalletReport_makeArcadeReport()
    {
        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: 'a242ef935b602ef8d3fe2abe4802d509', // "$request->gameRound",
                gameCode: $request->gameCode,
                betTime: '2024-01-01 12:00:00',
                opt: json_encode(['txn_id' => $request->gameRound])
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            walletReport: $mockWalletReport,
            wallet: $stubWallet
        );
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_mockWallet_payout()
    {
        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                credentials: $providerCredentials,
                playID: $request->playerId,
                currency: $request->currency,
                transactionID: "payout-{$request->gameRound}",
                amount: $request->amount,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            wallet: $mockWallet
        );
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_stubWalletWagerAndPayoutFail_ProviderWalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn(['status_code' => 51.31534]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $service->multiplayerSettle(request: $request);
    }

    public function test_multiplayerSettle_stubWallet_expectedData()
    {
        $expectedData = 1200.00;

        $request = new Request([
            'playerId' => 'testPlayID1',
            'agentId' => 111,
            'amount' => 200,
            'currency' => 'IDR',
            'gameCode' => 'testGameCode',
            'gameRound' => 'testGameRound1',
            'eventTime' => '2024-01-01T00:00:00-04:00'
        ]);
        $request->headers->set('Authorization', 'validToken');

        $stubRepository = $this->createMock(Hg5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) ['play_id' => 'testPlayID1']);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getAuthorizationToken')->willReturn('validToken');
        $providerCredentials->method('getAgentID')->willReturn(111);

        $stubCredentials = $this->createMock(Hg5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['updated_at' => null]);

        $stubWalletReport = $this->createMock(WalletReport::class);
        $stubWalletReport->method('makeArcadeReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'credit_after' => 1200.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            walletReport: $stubWalletReport,
            wallet: $stubWallet
        );
        $response = $service->multiplayerSettle(request: $request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }
}
