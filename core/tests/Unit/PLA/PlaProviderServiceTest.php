<?php

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Wallet\V1\ProvSys\Transfer\Report;
use App\GameProviders\V2\PLA\PlaProviderService;
use App\GameProviders\V2\PCA\Contracts\IRepository;
use App\GameProviders\V2\PLA\Contracts\ICredentials;
use App\GameProviders\V2\PCA\Contracts\IWalletReport;
use App\GameProviders\V2\PCA\Contracts\ICredentialSetter;
use App\GameProviders\V2\PLA\Exceptions\WalletErrorException;
use App\GameProviders\V2\PLA\Exceptions\InvalidTokenException;
use App\GameProviders\V2\PLA\Exceptions\PlayerNotFoundException;
use App\GameProviders\V2\PLA\Exceptions\InsufficientFundException;
use App\GameProviders\V2\PLA\Exceptions\TransactionNotFoundException;
use App\GameProviders\V2\PLA\Exceptions\RefundTransactionNotFoundException;

class PlaProviderServiceTest extends TestCase
{
    private function makeService($repository = null, $credentialSetter = null, $wallet = null, $report = null): PlaProviderService
    {
        $repository ??= $this->createStub(IRepository::class);
        $credentialSetter ??= $this->createStub(ICredentialSetter::class);
        $wallet ??= $this->createStub(IWallet::class);
        $report ??= $this->createStub(IWalletReport::class);

        return new PlaProviderService(repository: $repository, credentials: $credentialSetter, wallet: $wallet, report: $report);
    }

    public function test_authenticate_mockRepository_getPlayerByPlayID()
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

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('playerid')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $mockRepository);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_nullPlayer_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('playerid')
            ->willReturn(null);

        $service = $this->makeService(repository: $mockRepository);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

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

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with('playerid', 'TEST_authToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $mockRepository);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_nullToken_invalidTokenException()
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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_stubRepository_expected()
    {
        $expected = 'IDR';

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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->with('playerid', 'TEST_authToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $stubRepository);
        $response = $service->authenticate(request: $request);

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
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $mockRepository = $this->createMock(IRepository::class);
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
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

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
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $mockRepository = $this->createMock(IRepository::class);
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
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(IRepository::class);
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
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $mockCredentialSetter = $this->createMock(ICredentialSetter::class);
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
            credentialSetter: $mockCredentialSetter,
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
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubCredentials = $this->createMock(ICredentials::class);

        $stubCredentialSetter = $this->createMock(ICredentialSetter::class);
        $stubCredentialSetter->method('getCredentialsByCurrency')
            ->willReturn($stubCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with($stubCredentials, $player->play_id)
            ->willReturn([
                'status_code' => 2100,
                'credit' => 0.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentialSetter: $stubCredentialSetter,
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
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(IRepository::class);
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
            'currency' => 'IDR'
        ];

        $playGame = (object) [
            'play_id' => 'playerid',
            'token' => 'TEST_authToken',
            'expired' => 'FALSE'
        ];

        $stubRepository = $this->createMock(IRepository::class);
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

        $mockRepository = $this->createMock(IRepository::class);
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
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken'
        ]);

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->logout(request: $request);
    }

    public function test_logout_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

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

        $mockRepository = $this->createMock(IRepository::class);
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

        $stubRepository = $this->createMock(IRepository::class);
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

        $mockRepository = $this->createMock(IRepository::class);
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

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('playerid')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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
        $this->expectException(PlayerNotFoundException::class);

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

        $playGame = (object) ['token' => 'testToken'];

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

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

        $playGame = (object) ['token' => 'testToken'];

        $service = $this->makeService();
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

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with('playerid', $request->externalToken)
            ->willReturn($playGame);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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

        $stubRepository = $this->createMock(IRepository::class);
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
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_testToken',
            'gameRoundCode' => 'testGameRoundCode',
            'transactionCode' => 'testTransactionCode',
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'playerID', 'currency' => 'IDR'];
        $report = new Report();

        $expected = 200.00;

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 1000.00]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => $expected]);

        $service = $this->makeService(repository: $stubRepository, report: $stubReport, wallet: $stubWallet);
        $response = $service->bet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_bet_mockRepository_getTransactionByTransactionIDRefID()
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

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTransactionIDRefID')
            ->with($request->gameRoundCode, $request->transactionCode);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);


        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(ICredentialSetter::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with($providerCredentials, $player->play_id)
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $mockWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $mockWallet,
            credentialSetter: $stubCredentials
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
            'transactionDate' => '2021-01-01 00:00:00.000',
            'amount' => '100',
            'gameCodeName' => 'testGameCode'
        ]);

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);
        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(ICredentialSetter::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 0]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $stubWallet,
            credentialSetter: $stubCredentials
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
        $playGame = (object) ['token' => 'testToken'];
        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);


        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(ICredentialSetter::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 9999]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $stubWallet,
            credentialSetter: $stubCredentials
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_createBetTransaction()
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

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $mockRepository->expects($this->once())
            ->method('createBetTransaction')
            ->with(
                $player,
                $request,
                '2021-01-01 08:00:00'
            );

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);


        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

        $mockCredentials = $this->createMock(ICredentialSetter::class);
        $mockCredentials->expects($this->exactly(2))
            ->method('getCredentialsByCurrency')
            ->with($player->currency);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentialSetter: $mockCredentials,
            report: $stubReport,
            wallet: $stubWallet
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockReport_makeReport()
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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentialSetter = $this->createMock(ICredentialSetter::class);
        $stubCredentialSetter->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockReport = $this->createMock(IWalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeReport')
            ->with(
                $providerCredentials,
                $request->transactionCode,
                $request->gameCodeName,
                '2021-01-01 08:00:00'
            )
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $stubWallet->method('WagerAndPayout')
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentialSetter: $stubCredentialSetter,
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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(ICredentialSetter::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('WagerAndPayout')
            ->with(
                $providerCredentials,
                $player->play_id,
                $player->currency,
                "wagerPayout-{$request->transactionCode}",
                (float) $request->amount,
                "wagerPayout-{$request->transactionCode}",
                0,
                $report
            )
            ->willReturn(['status_code' => 2100, 'credit_after' => 100]);

        $mockWallet->method('balance')
            ->willReturn(['status_code' => 2100, 'credit' => 200]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $mockWallet,
            credentialSetter: $stubCredentials
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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);


        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn($playGame);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with('playerid')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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
        $service->settle($request);
    }

    public function test_settle_nullPlayer_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testCodeName'
        ]);

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle($request);
    }

    public function test_settle_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testCodeName'
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTransactionIDRefID')
            ->with($request->gameRoundCode, "L-{$request->requestId}")
            ->willReturn(null);

        $mockRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTransactionIDRefID')
            ->with($request->gameRoundCode, $request->pay['transactionCode'])
            ->willReturn(null);

        $mockRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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
        $service->settle($request);
    }

    public function test_settle_transactionAlreadyExistsWithoutWin_expected()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $expected = 100.00;

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getTransactionByTransactionIDRefID')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $expected = 990.00;

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getBetTransactionByTransactionID')
            ->with($request->gameRoundCode)
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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
        $service->settle($request);
    }

    public function test_settle_noBetTransaction_transactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn(null);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $mockRepository = $this->createMock(IRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('createLoseTransaction')
            ->with($player, $request);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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
        $service->settle($request);
    }

    public function test_settle_mockCredentialsBalance_getCredentialsByCurrency()
    {
        $request = new Request([
            'requestId' => 'TEST_requestToken',
            'username' => 'TEST_PLAYERID',
            'externalToken' => 'TEST_authToken',
            'gameRoundCode' => 'testGameRoundCode',
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

        $mockCredentials = $this->createMock(ICredentialSetter::class);
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
            credentialSetter: $mockCredentials,
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(ICredentialSetter::class);
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
            credentialSetter: $stubCredentials,
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(ICredentialSetter::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999,
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentialSetter: $stubCredentials,
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $mockRepository = $this->createMock(IRepository::class);
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

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $mockCredentials = $this->createMock(ICredentialSetter::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($player->currency);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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

        $service = $this->makeService(
            repository: $stubRepository,
            credentialSetter: $mockCredentials,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle($request);
    }

    public function test_settle_mockReport_makeReport()
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentialSetter = $this->createMock(ICredentialSetter::class);
        $stubCredentialSetter->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockReport = $this->createMock(IWalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeReport')
            ->with(
                $providerCredentials,
                $request->pay['transactionCode'],
                $request->gameCodeName,
                '2024-01-01 08:00:03'
            )
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

        $service = $this->makeService(
            repository: $stubRepository,
            credentialSetter: $stubCredentialSetter,
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(ICredentialSetter::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

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
                $report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 0.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentialSetter: $stubCredentials,
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
            ->willReturn($report);

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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $expected = 10.00;

        $report = new Report();

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionID')
            ->willReturn((object) []);

        $stubReport = $this->createMock(IWalletReport::class);
        $stubReport->method('makeReport')
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
            'gameCodeName' => 'testCodeName'
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

        $mockRepository = $this->createMock(IRepository::class);
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
        $this->expectException(PlayerNotFoundException::class);

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
            'gameCodeName' => 'testCodeName'
        ]);

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->refund($request);
    }

    public function test_refund_usernameWithoutKiosk_playerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

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
            'gameCodeName' => 'testCodeName'
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
            'gameCodeName' => 'testCodeName'
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

        $mockRepository = $this->createMock(IRepository::class);
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
            'gameCodeName' => 'testCodeName'
        ]);

        $player = (object) [
            'play_id' => 'playerid',
            'currency' => 'IDR'
        ];

        $stubRepository = $this->createMock(IRepository::class);
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
            'gameCodeName' => 'testCodeName'
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

        $mockRepository = $this->createMock(IRepository::class);
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
            'gameCodeName' => 'testCodeName'
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

        $mockRepository = $this->createMock(IRepository::class);
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
            'gameCodeName' => 'testCodeName'
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

        $mockRepository = $this->createMock(IRepository::class);
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
            'gameCodeName' => 'testCodeName'
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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $mockCredentials = $this->createMock(ICredentialSetter::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with($player->currency);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('resettle')
            ->willReturn([
                'credit_after' => 10.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, credentialSetter: $mockCredentials, wallet: $stubWallet);

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
            'gameCodeName' => 'testCodeName'
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

        $stubRepository = $this->createMock(IRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getBetTransactionByTransactionIDRefID')
            ->willReturn($betTransaction);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(ICredentialSetter::class);
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

        $service = $this->makeService(repository: $stubRepository, credentialSetter: $stubCredentials, wallet: $stubWallet);

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
            'gameCodeName' => 'testCodeName'
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

        $stubRepository = $this->createMock(IRepository::class);
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
            'gameCodeName' => 'testCodeName'
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

        $stubRepository = $this->createMock(IRepository::class);
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