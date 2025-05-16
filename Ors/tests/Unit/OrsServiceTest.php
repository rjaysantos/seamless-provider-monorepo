<?php

use Carbon\Carbon;
use Tests\TestCase;
use Providers\Ors\OrsApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Ors\OrsService;
use Providers\Ors\OgSignature;
use Providers\Ors\OrsRepository;
use Providers\Ors\OrsCredentials;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Ors\Contracts\ICredentials;
use Providers\Ors\Exceptions\WalletErrorException;
use Providers\Ors\Exceptions\InvalidTokenException;
use Providers\Ors\Exceptions\InsufficientFundException;
use Providers\Ors\Exceptions\InvalidPublicKeyException;
use Providers\Ors\Exceptions\InvalidSignatureException;
use Providers\Ors\Exceptions\TransactionAlreadyExistsException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Ors\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use Providers\Ors\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class OrsServiceTest extends TestCase
{
    private function makeService(
        OrsRepository $repository = null,
        OrsCredentials $credentials = null,
        OrsApi $api = null,
        OgSignature $encryption = null,
        IWallet $wallet = null,
        WalletReport $report = null
    ): OrsService {
        $repository ??= $this->createMock(OrsRepository::class);
        $credentials ??= $this->createMock(OrsCredentials::class);
        $api ??= $this->createMock(OrsApi::class);
        $encryption ??= $this->createMock(OgSignature::class);
        $wallet ??= $this->createMock(IWallet::class);
        $report ??= $this->createMock(WalletReport::class);

        return new OrsService(
            repository: $repository,
            credentials: $credentials,
            api: $api,
            encryption: $encryption,
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
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId);

        $service = $this->makeService(repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(playID: $request->playId, username: $request->username, currency: $request->currency);

        $service = $this->makeService(repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $mockCredentials = $this->createMock(OrsCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $service = $this->makeService(credentials: $mockCredentials);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createToken()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('createToken')
            ->with(playID: $request->playId);

        $service = $this->makeService(repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_enterGame()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'testLanguage'
        ]);

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('createToken')
            ->willReturn('testToken');

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(OrsApi::class);
        $mockApi->expects($this->once())
            ->method('enterGame')
            ->with(credentials: $providerCredentials, request: $request, token: 'testToken');

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            api: $mockApi
        );

        $service->getLaunchUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->play_id)
            ->willReturn((object) []);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_nullPlayer_playerNotFoundException()
    {
        $this->expectException(CasinoPlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(OrsRepository::class);
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

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->bet_id)
            ->willReturn((object) []);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_nullTransaction_transactionNotFoundException()
    {
        $this->expectException(CasinoTransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(OrsRepository::class);
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

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $mockCredentials = $this->createMock(OrsCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockApi_getBettingRecords()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testBetID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(OrsApi::class);
        $mockApi->expects($this->once())
            ->method('getBettingRecords')
            ->with(
                credentials: $providerCredentials,
                transactionID: $request->bet_id,
                playID: $request->play_id
            );

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, api: $mockApi);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_authenticate_mockRepository_getPlayerByPlayID()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->player_id)
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $service = $this->makeService(repository: $mockRepository, credentials: $stubCredentials, encryption: $stubEncryption);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_nullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $mockCredentials = $this->createMock(OrsCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency)
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, encryption: $stubEncryption);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_invalidPublicKey_invalidPublicKeyException()
    {
        $this->expectException(InvalidPublicKeyException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'invalidPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_mockEncryption_isSignatureValid()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockEncryption = $this->createMock(OgSignature::class);
        $mockEncryption->expects($this->once())
            ->method('isSignatureValid')
            ->with(request: $request, credentials: $stubProviderCredentials)
            ->willReturn(true);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $mockEncryption
        );
        $service->authenticate(request: $request);
    }

    public function test_authenticate_stubEncryptionInvalidSignature_invalidSignatureException()
    {
        $this->expectException(InvalidSignatureException::class);
        
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(false);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );
        $service->authenticate(request: $request);
    }

    public function test_authenticate_mockRepository_getPlayGameByPlayIDToken()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->expects($this->once())
            ->method('getPlayGameByPlayIDToken')
            ->with(playID: $request->player_id, token: $request->token)
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );
        $service->authenticate(request: $request);
    }

    public function test_authenticate_invalidToken_invalidTokenException()
    {
        $this->expectException(InvalidTokenException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn(null);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );
        $service->authenticate(request: $request);
    }

    public function test_getBalance_mockRepository_getPlayerByPlayID()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->player_id)
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_nullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $mockCredentials = $this->createMock(OrsCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency)
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_invalidPublicKey_invalidPublicKeyException()
    {
        $this->expectException(InvalidPublicKeyException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'invalidPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockEncryption_isSignatureValid()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockEncryption = $this->createMock(OgSignature::class);
        $mockEncryption->expects($this->once())
            ->method('isSignatureValid')
            ->with(request: $request, credentials: $stubProviderCredentials)
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $mockEncryption,
            wallet: $stubWallet
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubEncryptionInvalidSignature_invalidSignatureException()
    {
        $this->expectException(InvalidSignatureException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(false);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockWallet_balance()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $stubProviderCredentials, playID: $request->player_id)
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $mockWallet
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_invalidWalletResponse_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWallet_expectedData()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $expected = (object) [
            'balance' => 100.00,
            'currency' => $player->currency
        ];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => $expected->balance
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $response = $service->getBalance(request: $request);

        $this->assertEquals(expected: $expected, actual: $response);
    }

    public function test_bet_mockRepository_getPlayerByPlayID()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->player_id)
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_nullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $mockCredentials = $this->createMock(OrsCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency)
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_invalidPublicKey_invalidPublicKeyException()
    {
        $this->expectException(InvalidPublicKeyException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('invalidPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->bet(request: $request);
    }

    public function test_bet_mockEncryption_isSignatureValid()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockEncryption = $this->createMock(OgSignature::class);
        $mockEncryption->expects($this->once())
            ->method('isSignatureValid')
            ->with(request: $request, credentials: $stubProviderCredentials)
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $mockEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_stubEncryptionInvalidSignature_invalidSignatureException()
    {
        $this->expectException(InvalidSignatureException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(false);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_balance()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $stubProviderCredentials, playID: $request->player_id)
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $mockWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $mockWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_invalidBalanceWalletResponse_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->bet(request: $request);
    }

    public function test_bet_insufficientFund_insufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: 'test_transacID_1');

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_betTransactionAlreadyExists_transactionAlreadyExistsException()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_createBetTransaction()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => $date->timestamp,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('createBetTransaction')
            ->with(
                transactionID: 'test_transacID_1',
                betAmount: 150,
                betTime: $date->format('Y-m-d H:i:s')
            );

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockReport_makeSlotReport()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => $date->timestamp,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([]);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: 'test_transacID_1',
                gameCode: $request->game_id,
                betTime: $date->format('Y-m-d H:i:s')
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $mockReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockReport_makeArcadeReport()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 131,
                'round_id' => 'testRoundID',
                'called_at' => $date->timestamp,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn(['131']);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: 'test_transacID_1',
                gameCode: $request->game_id,
                betTime: $date->format('Y-m-d H:i:s')
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $mockReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockProviderCredentials_getArcadeGameList()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => $date->timestamp,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockProviderCredentials = $this->createMock(ICredentials::class);
        $mockProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $mockProviderCredentials->expects($this->once())
            ->method('getArcadeGameList')
            ->willReturn([]);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($mockProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_wager()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => $date->timestamp,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->player_id,
                currency: $player->currency,
                transactionID: 'wager-test_transacID_1',
                amount: 150,
                report: new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 850.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $mockWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_invalidWalletWagerResponse_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567890,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(originalClassName: WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 'invalid',
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bet(request: $request);
    }

    public function test_bet_stubWallet_expected()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567890,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $expected = 850.00;

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 2100,
                'credit' => 1000.00
            ]);

        $stubWallet->method('wager')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => $expected
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $response = $service->bet(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_rollback_mockRepository_getPlayerByPlayID()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->player_id)
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_nullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->rollback(request: $request);
    }

    public function test_rollback_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $mockCredentials = $this->createMock(OrsCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency)
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_invalidPublicKey_invalidPublicKeyException()
    {
        $this->expectException(InvalidPublicKeyException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('invalidPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->rollback(request: $request);
    }

    public function test_rollback_mockEncryption_isSignatureValid()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockEncryption = $this->createMock(OgSignature::class);
        $mockEncryption->expects($this->once())
            ->method('isSignatureValid')
            ->with(request: $request, credentials: $stubProviderCredentials)
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $mockEncryption,
            wallet: $stubWallet
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_stubEncryptionInvalidSignature_invalidSignatureException()
    {
        $this->expectException(InvalidSignatureException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(false);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_mockRepository_getBetTransactionByTrxID()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getBetTransactionByTrxID')
            ->with(transactionID: 'test_transacID_1')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_nullTransaction_expected()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn(null);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_mockRepository_cancelBetTransaction()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => $date->timestamp,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('cancelBetTransaction')
            ->with(transactionID: 'test_transacID_1', cancelTime: $date->format('Y-m-d H:i:s'));

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_mockWallet_cancel()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567890,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('cancel')
            ->with(
                credentials: $stubProviderCredentials,
                transactionID: 'cancelBet-test_transacID_1',
                amount: 150,
                transactionIDToCancel: 'wager-test_transacID_1'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 1000.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $mockWallet
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_invalidWalletResponse_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567890,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 'invalid',
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->rollback(request: $request);
    }

    public function test_rollback_stubWallet_expected()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 123,
                'round_id' => 'testRoundID',
                'called_at' => 1234567890,
                'records' => [
                    [
                        'transaction_id' => 'test_transacID_1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $expected = 1000.00;

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getBetTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => $expected
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $response = $service->rollback(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_settle_mockRepository_getPlayerByPlayID()
    {
        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 123456789,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->player_id)
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_nullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 123456789,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );
        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->settle(request: $request);
    }

    public function test_settle_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 123456789,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $mockCredentials = $this->createMock(OrsCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency)
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_invalidPublicKey_invalidPublicKeyException()
    {
        $this->expectException(InvalidPublicKeyException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'token' => 'testToken',
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'invalidPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->settle(request: $request);
    }

    public function test_settle_mockEncryption_isSignatureValid()
    {
        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 123456789,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockEncryption = $this->createMock(OgSignature::class);
        $mockEncryption->expects($this->once())
            ->method('isSignatureValid')
            ->with(request: $request, credentials: $stubProviderCredentials)
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $mockEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_stubEncryptionInvalidSignature_invalidSignatureException()
    {
        $this->expectException(InvalidSignatureException::class);

        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 123456789,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(false);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_getTransactionByTrxID()
    {
        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 123456789,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->transaction_id)
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_nullTransaction_transactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 123456789,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn(null);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockWallet_balance()
    {
        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 123456789,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => '2024-01-01 00:00:00'
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $stubProviderCredentials, playID: $request->player_id)
            ->willReturn([
                'status_code' => 2100,
                'credit' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $mockWallet
        );

        $service->settle(request: $request);
    }

    public function test_settle_invalidWalletErrorResponseBalance_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 123456789,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => '2024-01-01 00:00:00'
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'status_code' => 'invalid'
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet
        );

        $service->settle(request: $request);
    }

    public function test_settle_mockRepository_settleBetTransaction()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => $date->timestamp,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $mockRepository->expects($this->once())
            ->method('settleBetTransaction')
            ->with(
                transactionID: $request->transaction_id,
                winAmount: $request->amount,
                settleTime: $date->format('Y-m-d H:i:s')
            );

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle(request: $request);

        Carbon::setTestNow();
    }

    public function test_settle_mockReport_makeSlotReport()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => $date->timestamp,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: $request->transaction_id,
                gameCode: $request->game_id,
                betTime: $date->format('Y-m-d H:i:s')
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $mockReport
        );

        $service->settle(request: $request);

        Carbon::setTestNow();
    }

    public function test_settle_mockReport_makeArcadeReport()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => $date->timestamp,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([123]);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeArcadeReport')
            ->with(
                transactionID: $request->transaction_id,
                gameCode: $request->game_id,
                betTime: $date->format('Y-m-d H:i:s')
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $mockReport
        );

        $service->settle(request: $request);

        Carbon::setTestNow();
    }

    public function test_settle_mockWallet_payout()
    {
        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 1234567890,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('payout')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->player_id,
                currency: $player->currency,
                transactionID: "payout-{$request->transaction_id}",
                amount: $request->amount,
                report: new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $mockWallet,
            report: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_invalidWalletResponsePayout_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 1234567890,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 'invalid'
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->settle(request: $request);
    }

    public function test_settle_stubWallet_expected()
    {
        $request = new Request(
            query: [
                "player_id" => "testPlayerID",
                "amount" => 30,
                "transaction_id" => "testTransactionID",
                "transaction_type" => "credit",
                "round_id" => "testRoundID",
                "game_id" => 123,
                "currency" => "IDR",
                "called_at" => 1234567890,
                "signature" => "testSignature"
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $betTransaction = (object) [
            'trx_id' => $request->transaction_id,
            'updated_at' => null
        ];

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $expected = 100.00;

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn($betTransaction);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => $expected
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $response = $service->settle(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_bonus_mockRepository_getPlayerByPlayID()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 123456789,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->player_id)
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([]);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_nullPlayer_providerPlayerNotFoundException()
    {
        $this->expectException(ProviderPlayerNotFoundException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 123456789,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->bonus(request: $request);
    }

    public function test_bonus_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 123456789,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([]);

        $mockCredentials = $this->createMock(OrsCredentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $player->currency)
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $mockCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_invalidPublicKey_invalidPublicKeyException()
    {
        $this->expectException(InvalidPublicKeyException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 123456789,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'invalidPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials);
        $service->bonus(request: $request);
    }

    public function test_bonus_mockEncryption_isSignatureValid()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 123456789,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([]);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockEncryption = $this->createMock(OgSignature::class);
        $mockEncryption->expects($this->once())
            ->method('isSignatureValid')
            ->with(request: $request, credentials: $stubProviderCredentials)
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $mockEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_stubEncryptionInvalidSignature_invalidSignatureException()
    {
        $this->expectException(InvalidSignatureException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 123456789,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(false);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_mockRepository_getTransactionByTrxID()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 123456789,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(transactionID: $request->transaction_id)
            ->willReturn(null);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([]);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_transactionAlreadyExists_transactionAlreadyExistsException()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 123456789,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([]);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_mockRepository_createBonusTransaction()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => $date->timestamp,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $mockRepository = $this->createMock(OrsRepository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $mockRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('createBonusTransaction')
            ->with(
                transactionID: $request->transaction_id,
                bonusAmount: $request->amount,
                bonusTime: $date->format('Y-m-d H:i:s')
            );

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([]);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeBonusReport')
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $mockRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_mockRepository_makeBonusReport()
    {
        $date = Carbon::now()->setTimezone('GMT+8');

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => $date->timestamp,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([]);

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeBonusReport')
            ->with(
                transactionID: $request->transaction_id,
                gameCode: $request->game_code,
                betTime: $date->format('Y-m-d H:i:s')
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $mockReport
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_mockWallet_bonus()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 1234567890,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];
        $report = new Report;

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeBonusReport')
            ->willReturn($report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('bonus')
            ->with(
                credentials: $stubProviderCredentials,
                playID: $request->player_id,
                currency: $player->currency,
                transactionID: "bonus-{$request->transaction_id}",
                amount: $request->amount,
                report: $report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.00
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $mockWallet,
            report: $stubReport
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_invalidWalletResponse_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 1234567890,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];
        $report = new Report;

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeBonusReport')
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 'invalid',
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->bonus(request: $request);
    }

    public function test_bonus_stubWallet_expectedData()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'amount' => 100.00,
                'transaction_id' => 'testTransactionID',
                'game_code' => 123,
                'called_at' => 1234567890,
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $player = (object) ['play_id' => 'testPlayID', 'currency' => 'IDR'];
        $report = new Report;

        $expected = 100.0;

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($player);

        $stubRepository->method('getPlayGameByPlayIDToken')
            ->willReturn((object) []);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');

        $stubCredentials = $this->createMock(OrsCredentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubEncryption = $this->createMock(OgSignature::class);
        $stubEncryption->method('isSignatureValid')
            ->willReturn(true);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeBonusReport')
            ->willReturn($report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('bonus')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => $expected
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $response = $service->bonus(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }
}
