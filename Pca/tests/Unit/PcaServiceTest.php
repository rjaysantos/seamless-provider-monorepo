<?php

use Tests\TestCase;
use Providers\Pca\PcaApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Providers\Pca\PcaService;
use Providers\Pca\PcaRepository;
use Providers\Pca\PcaCredentials;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Pca\Contracts\ICredentials;
use PHPUnit\Framework\Attributes\DataProvider;
use Providers\Pca\Exceptions\WalletErrorException;
use Providers\Pca\Exceptions\InvalidTokenException;
use Providers\Pca\Exceptions\InsufficientFundException;
use Providers\Pca\Exceptions\RefundTransactionNotFoundException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Pca\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use Providers\Pca\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class PcaServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $randomizer = null,
        $wallet = null,
        $report = null
    ): PcaService {
        $repository ??= $this->createStub(PcaRepository::class);
        $credentials ??= $this->createStub(PcaCredentials::class);
        $api ??= $this->createStub(PcaApi::class);
        $randomizer ??= $this->createStub(Randomizer::class);
        $wallet ??= $this->createStub(IWallet::class);
        $report ??= $this->createStub(WalletReport::class);

        return new PcaService(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            randomizer: $randomizer,
            wallet: $wallet,
            report: $report
        );
    }

    public function test_getLaunchUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId);

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(
                playID: $request->playId,
                currency: $request->currency,
                username: $request->username
            );

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockCredentials = $this->createMock(PcaCredentials::class);

        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(credentials: $mockCredentials, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createOrUpdateToken()
    {
        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getKioskName')
            ->willReturn('testKioskName');

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->expects($this->once())
            ->method('createOrUpdateToken')
            ->with(playID: $request->playId, token: 'testKioskName_testToken');

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            api: $stubApi,
            randomizer: $stubRandomizer
        );
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_getGameLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getKioskName')
            ->willReturn('testKioskName');

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->expects($this->once())
            ->method('getGameLaunchUrl')
            ->with(credentials: $providerCredentials, request: $request, token: 'testKioskName_testToken')
            ->willReturn('testUrl.com');

        $service = $this->makeService(credentials: $stubCredentials, api: $stubApi, randomizer: $stubRandomizer);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expectedData()
    {
        $expected = 'testUrl.com';

        $request = new Request([
            'playId' => 'testplayid',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(api: $stubApi);
        $response = $service->getLaunchUrl(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_getBetDetail_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with($request->play_id)
            ->willReturn((object) []);

        $mockRepository->method('getTransactionByRefID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_stubRepository_playerNotFoundException()
    {
        $this->expectException(CasinoPlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockRepository_getTransactionByRefID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByRefID')
            ->with($request->bet_id)
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_stubRepository_transactionNotFoundException()
    {
        $this->expectException(CasinoTransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn(null);

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $mockCredentials = $this->createMock(PcaCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($request->currency);

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockApi_gameRoundStatus()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(PcaApi::class);
        $mockApi->expects($this->once())
            ->method('gameRoundStatus')
            ->with($providerCredentials, 'testTransactionID')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, api: $mockApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_stubApi_expectedData()
    {
        $expected = 'testUrl.com';

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testRefID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $stubApi = $this->createMock(PcaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $response = $service->getBetDetail(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_authenticate_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'testplayid',
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'testplayid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'testplayid')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $mockRepository);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_nullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_authToken'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'invalidUsername',
            'externalToken' => 'TEST_authToken'
        ]);

        $service = $this->makeService();
        $service->authenticate(request: $request);
    }

    public function test_authenticate_mockRepository_getPlayGameByPlayIDToken()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'testplayid',
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'testplayid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with(playID: 'testplayid', token: 'TEST_authToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $mockRepository);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_nullToken_invalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'testplayid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->authenticate(request: $request);
    }

    #[DataProvider('currencyAndCountryCode')]
    public function test_authenticate_stubRepositorySTG_expected($currency, $countryCode)
    {
        $expected = (object) [
            'countryCode' => 'CN',
            'currency' => 'CNY'
        ];

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'testplayid',
            'currency' => $currency
        ];

        $playGame = (object) [
            'play_id' => 'testplayid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $stubRepository);
        $response = $service->authenticate(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    #[DataProvider('currencyAndCountryCode')]
    public function test_authenticate_stubRepositoryPROD_expected($currency, $countryCode)
    {
        config(['app.env' => 'PRODUCTION']);

        $expected = (object) [
            'countryCode' => $countryCode,
            'currency' => $currency
        ];

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'testplayid',
            'currency' => $currency
        ];

        $playGame = (object) [
            'play_id' => 'testplayid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $stubRepository);
        $response = $service->authenticate(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public static function currencyAndCountryCode()
    {
        return [
            ['IDR', 'ID'],
            ['PHP', 'PH'],
            ['VND', 'VN'],
            ['USD', 'US'],
            ['THB', 'TH'],
            ['MYR', 'MY'],
        ];
    }

    public function test_getBalance_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR',
            'limit' => null
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('playerid')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_nullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'invalidUsername',
            'externalToken' => 'TEST_authToken'
        ]);

        $service = $this->makeService();
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockRepository_getPlayGameByPlayIDToken()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR',
            'limit' => null
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with('playerid', 'TEST_authToken')
            ->willReturn($playGame);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_nullToken_invalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR',
            'limit' => null
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentialSetter_getCredentialsByCurrency()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR',
            'limit' => null
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $mockCredentialSetter = $this->createMock(PcaCredentials::class);
        $mockCredentialSetter->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($player->currency);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentialSetter,
            wallet: $stubWallet
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockWallet_balance()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR',
            'limit' => null
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with($providerCredentials, $player->play_id)
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet
        );
        $service->getBalance(request: $request);
    }

    public function test_getBalance_invalidWalletResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR',
            'limit' => null
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWallet_expected()
    {
        $expected = 0.00;

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR',
            'limit' => null
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => $expected
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $response = $service->getBalance(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_logout_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('playerid')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $mockRepository);
        $service->logout(request: $request);
    }

    public function test_logout_nullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->logout(request: $request);
    }

    public function test_logout_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'invalidUsername',
            'externalToken' => 'TEST_authToken'
        ]);

        $service = $this->makeService();
        $service->logout(request: $request);
    }

    public function test_logout_mockRepository_getPlayGameByPlayIDToken()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with('playerid', 'TEST_authToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $mockRepository);
        $service->logout(request: $request);
    }

    public function test_logout_nullToken_invalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->logout(request: $request);
    }

    public function test_logout_mockRepository_deleteToken()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $mockRepository->expects($this->once())
            ->method('deleteToken');

        $service = $this->makeService(repository: $mockRepository);
        $service->logout(request: $request);
    }

    public function test_bet_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'testplayid')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(repository: $mockRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_stubRepository_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $playGame = (object) ['token' => 'testToken'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $service = $this->makeService();
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_getPlayGameByPlayIDToken()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with(playID: 'testplayid', token: $request->externalToken)
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(repository: $mockRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_nullToken_invalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_transactionAlreadyExists_expected()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];

        $expected = 200.00;

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getTransactionByBetID')
            ->willReturn((object)[]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => $expected]);

        $service = $this->makeService(repository: $stubRepository, report: $stubReport, wallet: $stubWallet);
        $response = $service->bet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_bet_mockRepository_getTransactionByBetID()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getTransactionByBetID')
            ->with(betID: $request->transactionCode);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(repository: $mockRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_balance()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);


        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: $player->play_id)
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $mockWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );

        $service->bet(request: $request);
    }

    public function test_bet_insufficientFunds_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);
        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 0]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->bet(request: $request);
    }

    public function test_bet_invalidWalletResponseBalance_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 9999]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_createTransaction()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                playID: 'testplayid',
                currency: 'IDR',
                gameCode: 'testGameCode',
                betID: 'testTransactionCode',
                betAmount: 100,
                winAmount: 0,
                betTime: '2024-01-01 08:00:00',
                status: 'WAGER',
                refID: 'testGameRoundCode'
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(
            repository: $mockRepository,
            report: $stubReport,
            wallet: $stubWallet
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);


        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $mockCredentials = $this->createMock(PcaCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            report: $stubReport,
            wallet: $stubWallet
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockReport_makeCasinoReport()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeCasinoReport')
            ->with(
                trxID: 'testTransactionCode',
                gameCode: 'testGameCode',
                betTime: '2024-01-01 08:00:00',
                betChoice: '-',
                result: '-'
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            report: $mockReport,
            wallet: $stubWallet
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_wagerAndPayout()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('WagerAndPayout')
            ->with(
                credentials: $providerCredentials,
                playID: $player->play_id,
                currency: $player->currency,
                wagerTransactionID: "wagerPayout-{$request->transactionCode}",
                wagerAmount: (float) $request->amount,
                payoutTransactionID: "wagerPayout-{$request->transactionCode}",
                payoutAmount: 0,
                report: new Report
            )
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $mockWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );

        $service->bet(request: $request);
    }

    public function test_bet_invalidWalletResponseWagerAndPayout_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 9999]);

        $service = $this->makeService(repository: $stubRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_stubWallet_expectedData()
    {
        $expected = 100.00;

        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_TESTPLAYID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2024-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testplayid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => $expected]);

        $service = $this->makeService(repository: $stubRepository, report: $stubReport, wallet: $stubWallet);

        $response = $service->bet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_settle_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('playerid')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
        $service->settle($request);
    }

    public function test_settle_nullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle($request);
    }

    public function test_settle_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $service = $this->makeService();
        $service->settle($request);
    }

    public function test_settle_mockRepositoryWithoutWin_getTransactionByTransactionIDRefID()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTransactionIDRefID')
            ->with($request->gameRoundCode, "L-{$request->requestId}")
            ->willReturn(null);

        $mockRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
        $service->settle($request);
    }

    public function test_settle_mockRepositoryWithWin_getTransactionByTransactionIDRefID()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTransactionIDRefID')
            ->with($request->gameRoundCode, $request->pay['transactionCode'])
            ->willReturn(null);

        $mockRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
        $service->settle($request);
    }

    public function test_settle_transactionAlreadyExistsWithoutWin_expected()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $expected = 100.00;

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getTransactionByTransactionIDRefID')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => $expected
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, report: $stubReport);
        $response = $service->settle($request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_settle_transactionAlreadyExistsWithWin_expected()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $expected = 990.00;

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => $expected
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, report: $stubReport);
        $response = $service->settle($request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_settle_mockRepository_getBetTransactionByTransactionID()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getBetTransactionByTransactionID')
            ->with($request->gameRoundCode)
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
        $service->settle($request);
    }

    public function test_settle_noBetTransaction_transactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn(null);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, report: $stubReport);
        $service->settle($request);
    }

    public function test_settle_mockRepository_createLoseTransaction()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('createLoseTransaction')
            ->with($player, $request);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
        $service->settle($request);
    }

    public function test_settle_mockCredentialsBalance_getCredentialsByCurrency()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $mockCredentials = $this->createMock(PcaCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($player->currency);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle($request);
    }

    public function test_settle_mockWallet_balance()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                $providerCredentials,
                'playerid'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $mockWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            report: $stubReport
        );

        $service->settle($request);
    }

    public function test_settle_invalidWalletResponseBalance_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999,
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle($request);
    }

    public function test_settle_mockRepository_createSettleTransaction()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('createSettleTransaction')
            ->with(
                $player,
                $request,
                '2024-01-01 08:00:03',
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
        $service->settle($request);
    }

    public function test_settle_mockCredentialsWagerAndPayout_getCredentialsByCurrency()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $mockCredentials = $this->createMock(PcaCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($player->currency);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle($request);
    }

    public function test_settle_mockReport_makeCasinoReport()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeCasinoReport')
            ->with(
                trxID: 'testTransactionCode',
                gameCode: 'testGameCode',
                betTime: '2024-01-01 08:00:03',
                betChoice: '-',
                result: '-'
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $mockReport
        );

        $service->settle($request);
    }

    public function test_settle_mockWallet_wagerAndPayout()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $mockWallet->expects($this->once())
            ->method('WagerAndPayout')
            ->with(
                $providerCredentials,
                $player->play_id,
                $player->currency,
                "wagerPayout-{$request->pay['transactionCode']}",
                0,
                "wagerPayout-{$request->pay['transactionCode']}",
                (float) $request->pay['amount'],
                new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            report: $stubReport
        );

        $service->settle($request);
    }

    public function test_settle_invalidWalletResponseWagerAndPayout_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle($request);
    }

    public function test_settle_stubWallet_payout()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:03.000',
                'amount' => '10',
                'type' => 'WIN'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $expected = 10.00;

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeCasinoReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => $expected
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            report: $stubReport
        );

        $response = $service->settle($request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_refund_stubRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode1',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $betTransaction = (object) [
            'trx_id' => 'testGameRoundCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'ref_id' => 'testRelatedTransactionCode'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('playerid')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->refund(request: $request);
    }

    public function test_refund_nullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->refund($request);
    }

    public function test_refund_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $service = $this->makeService();
        $service->refund($request);
    }

    public function test_refund_mockRepository_getBetTransactionByTransactionIDRefID()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $betTransaction = (object) [
            'trx_id' => 'testGameRoundCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'ref_id' => 'testRelatedTransactionCode'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getBetTransactionByTransactionIDRefID')
            ->with($request->gameRoundCode)
            ->willReturn($betTransaction);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->refund(request: $request);
    }

    public function test_refund_betTransactionNotFound_refundTransactionNotFoundException()
    {
        $this->expectException(RefundTransactionNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->refund(request: $request);
    }

    public function test_refund_mockRepository_getRefundTransactionByTransactionIDRefID()
    {
        $request = new Request([
            'requestId' => 'test_requestToken',
            'username' => 'test_playerID',
            'externalToken' => 'test_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerID',
            'currency' => 'IDR'
        ];

        $betTransaction = (object) [
            'trx_id' => 'testGameRoundCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'ref_id' => 'testRelatedTransactionCode'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $mockRepository->expects($this->once())
            ->method('getRefundTransactionByTransactionIDRefID')
            ->with($request->gameRoundCode, $request->pay['relatedTransactionCode'])
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->refund(request: $request);
    }

    public function test_refund_transactionAlreadySettled_TransactionAlreadyExistsException()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $betTransaction = (object) [
            'trx_id' => 'testGameRoundCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'ref_id' => 'testRelatedTransactionCode'
        ];

        $expected = 100.00;

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => $expected,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $response = $service->refund(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_refund_mockRepository_createRefundTransaction()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $betTransaction = (object) [
            'trx_id' => 'testGameRoundCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'ref_id' => 'testRelatedTransactionCode'
        ];

        $mockRepository = $this->createMock(PcaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $mockRepository->expects($this->once())
            ->method('createRefundTransaction')
            ->with(
                $player,
                $request,
                '2024-01-01 08:00:00'
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->refund(request: $request);
    }

    public function test_refund_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $betTransaction = (object) [
            'trx_id' => 'testGameRoundCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'ref_id' => 'testRelatedTransactionCode'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $mockCredentials = $this->createMock(PcaCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($player->currency);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, wallet: $stubWallet);

        $service->refund(request: $request);
    }

    public function test_refund_mockWallet_resettle()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $betTransaction = (object) [
            'trx_id' => 'testGameRoundCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 08:00:00',
            'updated_at' => null,
            'ref_id' => 'testRelatedTransactionCode'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PcaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->expects($this->once())
            ->method('resettle')
            ->with(
                credentials: $providerCredentials,
                playID: $player->play_id,
                currency: $player->currency,
                transactionID: "resettle-{$betTransaction->ref_id}",
                amount: (float) $request->pay['amount'],
                betID: $betTransaction->ref_id,
                settledTransactionID: "wager-{$betTransaction->ref_id}",
                betTime: '2024-01-01 08:00:00'
            )
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);

        $service->refund(request: $request);
    }

    public function test_refund_invalidWalletError_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $betTransaction = (object) [
            'trx_id' => 'testGameRoundCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'ref_id' => 'testRelatedTransactionCode'
        ];

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);

        $service->refund(request: $request);
    }

    public function test_refund_stubWallet_expected()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'pay' => [
                'transactionCode' => 'testTransactionCode',
                'transactionDate' => '2024-01-01 00:00:00.000',
                'amount' => '10',
                'type' => 'REFUND',
                'relatedTransactionCode' => 'testRelatedTransactionCode'
            ],
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $betTransaction = (object) [
            'trx_id' => 'testGameRoundCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'ref_id' => 'testRelatedTransactionCode'
        ];

        $expected = 10.00;

        $stubRepository = $this->createMock(PcaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => $expected,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);

        $response = $service->refund(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}
