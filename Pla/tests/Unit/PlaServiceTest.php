<?php

use Carbon\Carbon;
use Tests\TestCase;
use Providers\Pla\PlaApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Providers\Pla\PlaService;
use Providers\Pla\PlaRepository;
use Providers\Pla\PlaCredentials;
use Providers\Pla\DTO\PlaPlayerDTO;
use Providers\Pla\DTO\PlaRequestDTO;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Pla\Contracts\ICredentials;
use Providers\Pla\Exceptions\WalletErrorException;
use Providers\Pla\Exceptions\InvalidTokenException;
use Providers\Pla\Exceptions\InsufficientFundException;
use Providers\Pla\Exceptions\RefundTransactionNotFoundException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Pla\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use Providers\Pla\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class PlaServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $randomizer = null,
        $wallet = null,
        $report = null
    ): PlaService {
        $repository ??= $this->createStub(PlaRepository::class);
        $credentials ??= $this->createStub(PlaCredentials::class);
        $api ??= $this->createStub(PlaApi::class);
        $randomizer ??= $this->createStub(Randomizer::class);
        $wallet ??= $this->createStub(IWallet::class);
        $report ??= $this->createStub(WalletReport::class);

        return new PlaService(
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
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

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
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(
                playID: $request->playId,
                currency: $request->currency,
                username: $request->username
            );

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $mockRepository, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $mockCredentials = $this->createMock(PlaCredentials::class);

        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('getGameLaunchUrl')
            ->willReturn('testUrl.com');

        $service = $this->makeService(credentials: $mockCredentials, api: $stubApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createOrUpdateToken()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getKioskName')
            ->willReturn('testKioskName');

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->expects($this->once())
            ->method('createOrUpdateToken')
            ->with(playID: $request->playId, token: 'testKioskName_testToken');

        $stubApi = $this->createMock(PlaApi::class);
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
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getKioskName')
            ->willReturn('testKioskName');

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $mockApi = $this->createMock(PlaApi::class);
        $mockApi->expects($this->once())
            ->method('getGameLaunchUrl')
            ->with(credentials: $providerCredentials, request: $request, token: 'testKioskName_testToken')
            ->willReturn('testUrl.com');

        $service = $this->makeService(credentials: $stubCredentials, api: $mockApi, randomizer: $stubRandomizer);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expectedData()
    {
        $expected = 'testUrl.com';

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'language' => 'en',
            'gameId' => 'testGameID',
            'device' => 1
        ]);

        $stubApi = $this->createMock(PlaApi::class);
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
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->play_id)
            ->willReturn((object) []);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $stubApi = $this->createMock(PlaApi::class);
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
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->bet_id)
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $stubApi = $this->createMock(PlaApi::class);
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
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $mockCredentials = $this->createMock(PlaCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, api: $stubApi);
        $service->getBetDetail(request: $request);
    }

    public function test_getBetDetail_mockApi_gameRoundStatus()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(PlaApi::class);
        $mockApi->expects($this->once())
            ->method('gameRoundStatus')
            ->with(credentials: $providerCredentials, transactionID: 'testRefID')
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID', 'ref_id' => 'testRefID']);

        $stubApi = $this->createMock(PlaApi::class);
        $stubApi->method('gameRoundStatus')
            ->willReturn('testUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $response = $service->getBetDetail(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_authenticate_mockRepository_getPlayerByPlayID()
    {
        $requestDTO = new PlaRequestDTO(
            requestId: 'TEST_requestToken',
            username: 'TEST_PLAYERID',
            token: 'TEST_authToken'
        );

        $player = new PlaPlayerDTO(
            playID: 'playerid',
            currency: 'IDR',
        );

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'playerid')
            ->willReturn($player);

        $mockRepository->method('getPlayerByPlayIDToken')
            ->willReturn($player);

        $service = $this->makeService(repository: $mockRepository);
        $service->authenticate(requestDTO: $requestDTO);
    }

    public function test_authenticate_nullPlayer_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $requestDTO = new PlaRequestDTO(
            requestId: 'TEST_requestToken',
            username: 'TEST_PLAYERID',
            token: 'TEST_authToken'
        );

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->authenticate(requestDTO: $requestDTO);
    }

    public function test_authenticate_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $requestDTO = new PlaRequestDTO(
            requestId: 'TEST_requestToken',
            username: 'TEST_PLAYERID',
            token: 'TEST_authToken'
        );

        $service = $this->makeService();
        $service->authenticate(requestDTO: $requestDTO);
    }

    public function test_authenticate_mockRepository_getPlayerByPlayIDToken()
    {
        $requestDTO = new PlaRequestDTO(
            requestId: 'TEST_requestToken',
            username: 'TEST_PLAYERID',
            token: 'TEST_authToken'
        );

        $player = new PlaPlayerDTO(
            playID: 'playerid',
            currency: 'IDR'
        );

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayIDToken')
            ->with(playID: 'playerid', token: 'TEST_authToken')
            ->willReturn($player);

        $service = $this->makeService(repository: $mockRepository);
        $service->authenticate(requestDTO: $requestDTO);
    }

    public function test_authenticate_nullToken_invalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $requestDTO = new PlaRequestDTO(
            requestId: 'TEST_requestToken',
            username: 'TEST_PLAYERID',
            token: 'token'
        );

        $player = new PlaPlayerDTO(
            playID: 'playerid',
            currency: 'IDR'
        );

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayerByPlayIDToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->authenticate(requestDTO: $requestDTO);
    }

    public function test_authenticate_stubRepository_expected()
    {
        $expected = 'IDR';

        $requestDTO = new PlaRequestDTO(
            requestId: 'TEST_requestToken',
            username: 'TEST_PLAYERID',
            token: 'TEST_authToken'
        );

        $player = new PlaPlayerDTO(
            playID: 'playerid',
            currency: 'IDR'
        );

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayerByPlayIDToken')
            ->with(playID: 'playerid', token: 'TEST_authToken')
            ->willReturn($player);

        $service = $this->makeService(repository: $stubRepository);
        $response = $service->authenticate(requestDTO: $requestDTO);

        $this->assertEquals(expected: $expected, actual: $response);
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

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'playerid')
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

        $stubRepository = $this->createMock(PlaRepository::class);
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

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with(playID: 'playerid', token: 'TEST_authToken')
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentials_getCredentialsByCurrency()
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $mockCredentials = $this->createMock(PlaCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, wallet: $stubWallet);
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: $player->play_id)
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
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

        $stubRepository = $this->createMock(PlaRepository::class);
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

        $stubRepository = $this->createMock(PlaRepository::class);
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

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'playerid')
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

        $stubRepository = $this->createMock(PlaRepository::class);
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

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with(playID: 'playerid', token: 'TEST_authToken')
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

        $stubRepository = $this->createMock(PlaRepository::class);
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

        $mockRepository = $this->createMock(PlaRepository::class);
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
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'playerid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'playerid')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

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
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $service = $this->makeService();
        $service->bet(request: $request);
    }

    public function test_bet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'playerid', 'currency' => 'IDR'];
        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);


        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $mockCredentials = $this->createMock(PlaCredentials::class);
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

    public function test_bet_mockWallet_balance()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
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

    public function test_bet_invalidWalletResponseBalance_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 9999]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'playerid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->transactionCode);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(repository: $mockRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_transactionAlreadyExists_expected()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'playerID', 'currency' => 'IDR'];

        $expected = 1000.00;

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 1000.00]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $response = $service->bet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
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
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 0]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_getPlayGameByPlayIDToken()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'playerid', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with(playerID: 'playerid', token: $request->externalToken)
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

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
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'playerid', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(PlaRepository::class);
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

    public function test_bet_mockRepository_createTransaction()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                trxID: 'testTransactionCode',
                betAmount: 100.0,
                winAmount: 0,
                betTime: '2021-01-01 08:00:00',
                settleTime: null,
                refID: 'testGameRoundCode'
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(repository: $mockRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_mockReport_makeSlotReport()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'playerid', 'currency' => 'IDR'];
        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: $request->transactionCode,
                gameCode: $request->gameCodeName,
                betTime: '2021-01-01 08:00:00'
            )
            ->willReturn($report);

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

    public function test_bet_mockReport_makeArcadeReport()
    {
        $request = new Request([
            'requestId' => 'testRequestID',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'playerid', 'currency' => 'IDR'];
        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getArcadeGameList')
            ->willReturn(['testGameCode']);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: $request->transactionCode,
                gameCode: $request->gameCodeName,
                betTime: '2021-01-01 08:00:00'
            )
            ->willReturn($report);

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
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
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
                report: $report
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
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);


        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

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
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

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

        $report = new Report();

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: 'playerid')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

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
        $service->settle(request: $request);
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
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
        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_getBetTransactionByRefID()
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

        $report = new Report();

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getBetTransactionByRefID')
            ->with(refID: $request->gameRoundCode)
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

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
        $service->settle(request: $request);
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_mockRepositoryWithoutWin_getTransactionByTrxID()
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

        $report = new Report();

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: "L-{$request->requestId}")
            ->willReturn(null);

        $mockRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

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
        $service->settle(request: $request);
    }

    public function test_settle_mockRepositoryWithWin_getTransactionByTrxID()
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

        $report = new Report();

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->pay['transactionCode'])
            ->willReturn(null);

        $mockRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

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
        $service->settle(request: $request);
    }

    public function test_settle_mockCredentials_getCredentialsByCurrency()
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

        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $mockCredentials = $this->createMock(PlaCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency);

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
        $service->settle(request: $request);
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

        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: 'playerid')
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
        $service->settle(request: $request);
    }

    public function test_settle_invalidWalletResponseBalance_walletErrorException()
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
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
            wallet: $stubWallet
        );
        $service->settle(request: $request);
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

        $expected = 1000.00;

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);
            
        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => $expected
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $response = $service->settle(request: $request);

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

        $expected = 1000.00;

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => $expected
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $response = $service->settle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_settle_mockRepositoryWithoutWin_createTransaction()
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

        Carbon::setTestNow('2024-01-01 00:00:00');

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                trxID: 'L-TEST_requestToken', 
                betAmount: 0,
                winAmount: 0,
                betTime: '2024-01-01 00:00:00',
                settleTime: '2024-01-01 00:00:00',
                refID: 'testGameRoundCode'
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->settle(request: $request);
    }

    public function test_settle_stubRepositoryWithoutWin_expected() 
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

        $expected = 1000.0;

        $stubRepsitory = $this->createMock(PlaRepository::class);
        $stubRepsitory->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepsitory->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => $expected
            ]);

        $service = $this->makeService(repository: $stubRepsitory, wallet: $stubWallet);
        $response = $service->settle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_settle_mockRepositoryWithWin_createTransaction()
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

        $report = new Report();

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                trxID: 'testTransactionCode',
                betAmount: 0,
                winAmount: 10.0,
                betTime: '2024-01-01 08:00:03',
                settleTime: '2024-01-01 08:00:03',
                refID: 'testGameRoundCode'
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 990.00
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
        $service->settle(request: $request);
    }

    public function test_settle_mockReport_makeSlotReport()
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

        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: $request->pay['transactionCode'],
                gameCode: $request->gameCodeName,
                betTime: '2024-01-01 08:00:03'
            )
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 990.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $mockReport
        );
        $service->settle(request: $request);
    }

    public function test_settle_mockReport_makeArcadeReport()
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

        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);
        $providerCredentials->method('getArcadeGameList')
            ->willReturn(['testGameCode']);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: $request->pay['transactionCode'],
                gameCode: $request->gameCodeName,
                betTime: '2024-01-01 08:00:03'
            )
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 990.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $stubWallet,
            report: $mockReport
        );
        $service->settle(request: $request);
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

        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $mockWallet->expects($this->once())
            ->method('WagerAndPayout')
            ->with(
                credentials: $providerCredentials,
                playID: $player->play_id,
                currency: $player->currency,
                wagerTransactionID: "wagerPayout-{$request->pay['transactionCode']}",
                wagerAmount: 0,
                payoutTransactionID: "wagerPayout-{$request->pay['transactionCode']}",
                payoutAmount: (float) $request->pay['amount'],
                report: $report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 990.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            report: $stubReport
        );
        $service->settle(request: $request);
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

        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, report: $stubReport);
        $service->settle(request: $request);
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

        $expected = 990.00;

        $report = new Report();

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByRefID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => $expected
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, report: $stubReport);
        $response = $service->settle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_refund_mockRepository_getPlayerByPlayID()
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

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playerID: 'playerid')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->refund(request: $request);
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
        $service->refund(request: $request);
    }

    public function test_refund_mockRepository_getBetTransactionByTrxID()
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

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getBetTransactionByTrxID')
            ->with(trxID: $request->pay['relatedTransactionCode'])
            ->willReturn((object) []);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $mockCredentials = $this->createMock(PlaCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, wallet: $stubWallet, report: $stubReport);
        $service->refund(request: $request);
    }

    public function test_refund_mockRepository_getTransactionByRefID()
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

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByRefID')
            ->with(refID: $request->pay['relatedTransactionCode'])
            ->willReturn(null);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
        $service->refund(request: $request);
    }

    public function test_refund_mockWallet_balance()
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $providerCredentials, playID: 'playerid')
            ->willReturn([
                'credit' => 1000.0,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
        $service->refund(request: $request);
    }

    public function test_refund_invalidWalletResponseBalance_walletErrorException()
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 999
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $stubWallet);
        $service->refund(request: $request);
    }

    public function test_refund_transactionAlreadySettled_expected()
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

        $expected = 100.00;

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByRefID')
            ->willReturn((object) []);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => $expected,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $response = $service->refund(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_refund_mockRepository_createTransaction()
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

        $mockRepository = $this->createMock(PlaRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('createTransaction')
            ->with(
                trxID: 'testTransactionCode',
                betAmount: 10.0,
                wiNAmount: 10.0,
                betTime: '2024-01-01 08:00:00',
                settleTime: '2024-01-01 08:00:00',
                refID: 'testRelatedTransactionCode'
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet, report: $stubReport);
        $service->refund(request: $request);
    }

    public function test_refund_mockWallet_wagerAndPayout()
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
            'trx_id' => 'testRelatedTransactionCode',
            'bet_amount' => 10,
            'win_amount' => 0,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => null,
            'ref_id' => 'testGameRoundCode'
        ];

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn($betTransaction);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(PlaCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('wagerAndPayout')
            ->with(
                credentials: $providerCredentials,
                playID: $player->play_id,
                currency: $player->currency,
                wagerTransactionID: "wagerPayout-{$request->pay['transactionCode']}",
                wagerAmount: 0,
                payoutTransactionID: "wagerPayout-{$request->pay['transactionCode']}",
                payoutAmount: (float) $request->pay['amount'],
                report: new Report
            )
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            wallet: $mockWallet,
            report: $stubReport
        );
        $service->refund(request: $request);
    }

    public function test_refund_invalidWalletResponseWagerAndPayout_walletErrorException()
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn($betTransaction);
        
        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, report: $stubReport);
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

        $stubRepository = $this->createMock(PlaRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('wagerAndPayout')
            ->willReturn([
                'credit_after' => $expected,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, report: $stubReport);
        $response = $service->refund(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}
