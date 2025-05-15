<?php

use Tests\TestCase;
use Providers\Gs5\Gs5Api;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\Libraries\Randomizer;
use Providers\Gs5\Gs5Service;
use Providers\Gs5\Gs5Repository;
use Providers\Gs5\Gs5Credentials;
use Wallet\V1\ProvSys\Transfer\Report;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Gs5\Contracts\ICredentials;
use Providers\Gs5\Credentials\Gs5Staging;
use App\Exceptions\Casino\PlayerNotFoundException;
use Providers\Gs5\Exceptions\WalletErrorException;
use Providers\Gs5\Exceptions\TokenNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Gs5\Exceptions\TransactionAlreadySettledException;
use Providers\Gs5\Exceptions\InsufficientFundException;
use Providers\Gs5\Exceptions\ProviderWalletErrorException;
use Providers\Gs5\Exceptions\TransactionAlreadyExistsException;
use Providers\Gs5\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class Gs5ServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $wallet = null,
        $report = null,
        $randomizer = null,
        $api = null
    ): Gs5Service {
        $repository ??= $this->createStub(Gs5Repository::class);
        $credentials ??= $this->createStub(Gs5Credentials::class);
        $api ??= $this->createStub(Gs5Api::class);
        $wallet ??= $this->createStub(IWallet::class);
        $report ??= $this->createStub(WalletReport::class);
        $randomizer ??= $this->createStub(Randomizer::class);
        $api ??= $this->createStub(Gs5Api::class);

        return new Gs5Service(
            repository: $repository,
            credentials: $credentials,
            wallet: $wallet,
            report: $report,
            randomizer: $randomizer,
            api: $api
        );
    }

    public function test_getBalance_mockRepository_getPlayerByToken()
    {
        $request = new Request(['access_token' => 'testToken']);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByToken')
            ->with(token: $request->access_token)
            ->willReturn((object) [
                'currency' => 'IDR',
                'play_id' => 'testPlayID'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubRepositoryNullPlayer_TokenNotFoundException()
    {
        $this->expectException(TokenNotFoundException::class);

        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'currency' => 'IDR',
                'play_id' => 'testPlayID'
            ]);

        $mockCredentials = $this->createMock(Gs5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, credentials: $mockCredentials);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_mockWallet_balance()
    {
        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'currency' => 'IDR',
                'play_id' => 'testPlayID'
            ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $stubProviderCredentials, playID: 'testPlayID')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $mockWallet, credentials: $stubCredentials);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWalletInvalidCode_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'currency' => 'IDR',
                'play_id' => 'testPlayID'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 448443]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->getBalance(request: $request);
    }

    public function test_getBalance_stubWallet_expectedData()
    {
        $expected = 100000.00;

        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'currency' => 'IDR',
                'play_id' => 'testPlayID'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $response = $service->getBalance(request: $request);

        $this->assertSame(expected: $expected, actual: $response);
    }

    public function test_authenticate_mockRepository_getPlayerByToken()
    {
        $request = new Request(['access_token' => 'testToken']);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByToken')
            ->with(token: $request->access_token)
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_stubRepositoryNullToken_TokenNotFoundException()
    {
        $this->expectException(TokenNotFoundException::class);

        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(Gs5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet, credentials: $mockCredentials);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_mockWallet_balance()
    {
        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(credentials: $stubProviderCredentials, playID: 'testPlayID')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->authenticate(request: $request);
    }

    public function test_authenticate_stubWalletError_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 41453535]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->authenticate(request: $request);
    }

    public function test_authenticate_stubResponse_expectedData()
    {
        $expectedData = (object) [
            'member_id' => 'testPlayID',
            'member_name' => 'testUsername',
            'balance' => 100000
        ];

        $request = new Request(['access_token' => 'testToken']);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'username' => 'testUsername',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $response = $service->authenticate(request: $request);

        $this->assertEquals(expected: $expectedData, actual: $response);
    }

    public function test_cancel_mockRepository_getPlayerByToken()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByToken')
            ->with(token: 'testToken')
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => '123',
                'bet_amount' => 1000,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => null
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->cancel(request: $request);
    }

    public function test_cancel_tokenNotFound_tokenNotFoundException()
    {
        $this->expectException(TokenNotFoundException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->cancel(request: $request);
    }

    public function test_cancel_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->method('getPlayerByToken')
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: '123')
            ->willReturn((object) [
                'trx_id' => '123',
                'bet_amount' => 1000,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => null
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->cancel(request: $request);
    }

    public function test_cancel_nullTransaction_ProviderTransactionNotFoundException()
    {
        $this->expectException(ProviderTransactionNotFoundException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->cancel(request: $request);
    }

    public function test_cancel_transactionAlreadySettled_transactionAlreadySettledException()
    {
        $this->expectException(TransactionAlreadySettledException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => '123',
                'bet_amount' => 1000,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => '2025-01-01 00:00:00'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->cancel(request: $request);
    }

    public function test_cancel_mockRepository_settleTransaction()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->method('getPlayerByToken')
            ->willReturn((object) ['currency' => 'IDR']);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => '123',
                'bet_amount' => 1000,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => null
            ]);

        $mockRepository->expects($this->once())
            ->method('settleTransaction')
            ->with(
                trxID: '123',
                winAmount: 1000,
                settleTime: '2025-01-01 00:00:00'
            );

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(repository: $mockRepository, wallet: $stubWallet);
        $service->cancel(request: $request);
    }

    public function test_cancel_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => '123',
                'bet_amount' => 1000,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => null
            ]);

        $mockCredentials = $this->createMock(Gs5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials, wallet: $stubWallet);
        $service->cancel(request: $request);
    }

    public function test_cancel_mockWallet_cancel()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => '123',
                'bet_amount' => 1000,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => null
            ]);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('cancel')
            ->with(
                credentials: $providerCredentials,
                transactionID: 'cancel-123',
                amount: 1000,
                transactionIDToCancel: 'wager-123'
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, wallet: $mockWallet);
        $service->cancel(request: $request);
    }

    public function test_cancel_walletInvalidResponse_walletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => '123',
                'bet_amount' => 1000,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => null
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 9999
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->cancel(request: $request);
    }

    public function test_cancel_stubWallet_expected()
    {
        $expected = 200000.0;

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 123
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) ['currency' => 'IDR']);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) [
                'trx_id' => '123',
                'bet_amount' => 1000,
                'created_at' => '2025-01-01 00:00:00',
                'updated_at' => null
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('cancel')
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 2000.0
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $result = $service->cancel(request: $request);

        $this->assertSame(expected: $expected, actual: $result);
    }

    public function test_getLaunchUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'en'
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->playId);

        $service = $this->makeService(repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_NoPlayerExistingmockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'en'
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $mockRepository->expects($this->once())
            ->method('createPlayer')
            ->with(
                playID: 'testPlayID',
                username: 'testUsername',
                currency: 'IDR'
            );

        $service = $this->makeService(repository: $mockRepository);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_HasPlayerExistingmockRepository_createPlayer()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'en'
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object)['test']);

        $mockRepository->expects($this->exactly(0))
            ->method('createPlayer')
            ->with(
                playID: 'testPlayID',
                username: 'testUsername',
                currency: 'IDR'
            );

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
            'language' => 'en'
        ]);

        $mockCredentials = $this->createMock(Gs5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $service = $this->makeService(credentials: $mockCredentials);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRandomizer_createToken()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'en'
        ]);

        $mockRandomizer = $this->createMock(Randomizer::class);
        $mockRandomizer->expects($this->once())
            ->method('createToken');

        $service = $this->makeService(randomizer: $mockRandomizer);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockRepository_createOrUpdatePlayGame()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'en'
        ]);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->expects($this->once())
            ->method('createOrUpdatePlayGame')
            ->with(
                playID: $request->playId,
                token: 'testToken'
            );

        $service = $this->makeService(repository: $mockRepository, randomizer: $stubRandomizer);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_mockApi_getLaunchUrl()
    {
        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'en'
        ]);

        $credentials = new Gs5Staging;

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($credentials);

        $stubRandomizer = $this->createMock(Randomizer::class);
        $stubRandomizer->method('createToken')
            ->willReturn('testToken');

        $mockApi = $this->createMock(Gs5Api::class);
        $mockApi->expects($this->once())
            ->method('getLaunchUrl')
            ->with(
                credentials: $credentials,
                playerToken: 'testToken',
                gameID: $request->gameId,
                lang: $request->language
            );

        $service = $this->makeService(credentials: $stubCredentials, randomizer: $stubRandomizer, api: $mockApi);
        $service->getLaunchUrl(request: $request);
    }

    public function test_getLaunchUrl_stubApi_expectedResponse()
    {
        $expectedResponse = 'test_url';

        $request = new Request([
            'playId' => 'testPlayID',
            'username' => 'testUsername',
            'currency' => 'IDR',
            'gameId' => 'testGameID',
            'language' => 'en'
        ]);

        $stubApi = $this->createMock(Gs5Api::class);
        $stubApi->method('getLaunchUrl')
            ->willReturn($expectedResponse);

        $service = $this->makeService(api: $stubApi);
        $result = $service->getLaunchUrl(request: $request);

        $this->assertSame(expected: $expectedResponse, actual: $result);
    }

    public function test_getBetDetailUrl_mockRepository_getPlayerByPlayID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByPlayID')
            ->with(playID: $request->play_id)
            ->willReturn((object) []);

        $mockRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepositoryNullPlayer_PlayerNotFoundException()
    {
        $this->expectException(PlayerNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->bet_id)
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $service = $this->makeService(repository: $mockRepository);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubRepositoryNullTransaction_TransactionNotFoundException()
    {
        $this->expectException(TransactionNotFoundException::class);

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
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
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $mockCredentials = $this->createMock(Gs5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: $request->currency);

        $service = $this->makeService(repository: $stubRepository, credentials: $mockCredentials);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_mockApi_getGameHistory()
    {
        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $providerCredentials = $this->createMock(ICredentials::class);

        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($providerCredentials);

        $mockApi = $this->createMock(Gs5Api::class);
        $mockApi->expects($this->once())
            ->method('getGameHistory')
            ->with(credentials: $providerCredentials, transactionID: $request->bet_id);

        $service = $this->makeService(repository: $stubRepository, credentials: $stubCredentials, api: $mockApi);
        $service->getBetDetailUrl(request: $request);
    }

    public function test_getBetDetailUrl_stubApi_expectedData()
    {
        $expectedData = 'testVisualUrl.com';

        $request = new Request([
            'play_id' => 'testPlayID',
            'bet_id' => 'testTransactionID',
            'currency' => 'IDR'
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn((object) []);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) ['trx_id' => 'testTransactionID']);

        $stubApi = $this->createMock(Gs5Api::class);
        $stubApi->method('getGameHistory')
            ->willReturn('testVisualUrl.com');

        $service = $this->makeService(repository: $stubRepository, api: $stubApi);
        $response = $service->getBetDetailUrl(request: $request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }

    public function test_bet_mockRepository_getPlayerByToken()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->expects($this->once())
            ->method('getPlayerByToken')
            ->with(token: $request->access_token)
            ->willReturn((object) [
                'currency' => 'IDR',
                'play_id' => 'testPlayID'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 3000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_stubRepositoryNullPlayer_TokenNotFoundException()
    {
        $this->expectException(TokenNotFoundException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn(null);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_getTransactionByTrxID()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockRepository->expects($this->once())
            ->method('getTransactionByTrxID')
            ->with(trxID: $request->txn_id)
            ->willReturn(null);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_stubRepositoryTransactionNotNull_TransactionAlreadyExistException()
    {
        $this->expectException(TransactionAlreadyExistsException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubRepository->method('getTransactionByTrxID')
            ->willReturn((object) []);

        $service = $this->makeService(repository: $stubRepository);
        $service->bet(request: $request);
    }

    public function test_bet_mockCredentials_getCredentialsByCurrency()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $mockCredentials = $this->createMock(Gs5Credentials::class);
        $mockCredentials->expects($this->once())
            ->method('getCredentialsByCurrency')
            ->with(currency: 'IDR');

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $stubWallet,
            credentials: $mockCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_balance()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->expects($this->once())
            ->method('balance')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID'
            )
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWallet_InsufficientFundException()
    {
        $this->expectException(InsufficientFundException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 100000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'currency' => 'IDR',
                'play_id' => 'testPlayID'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_stubWalletBalanceInvalidStatus_WalletErrorException()
    {
        $this->expectException(WalletErrorException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn(['status_code' => 999]);

        $service = $this->makeService(repository: $stubRepository, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_mockRepository_createWagerTransaction()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $mockRepository = $this->createMock(Gs5Repository::class);
        $mockRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockRepository->expects($this->once())
            ->method('createWagerTransaction')
            ->with(
                trxID: $request->txn_id,
                betAmount: 100.00,
                transactionDate: '2024-01-01 00:00:00'
            );

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $mockRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_mockReport_makeSlotReport()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $mockReport = $this->createMock(WalletReport::class);
        $mockReport->expects($this->once())
            ->method('makeSlotReport')
            ->with(
                transactionID: $request->txn_id,
                gameCode: $request->game_id,
                betTime: '2024-01-01 00:00:00'
            )
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, report: $mockReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_mockWallet_wager()
    {
        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubCredentials = $this->createMock(Gs5Credentials::class);
        $stubCredentials->method('getCredentialsByCurrency')
            ->willReturn($stubProviderCredentials);

        $mockWallet = $this->createMock(IWallet::class);
        $mockWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $mockWallet->expects($this->once())
            ->method('wager')
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID',
                currency: 'IDR',
                transactionID: "wager-12345",
                amount: 100.0,
                report: new Report
            )
            ->willReturn([
                'credit_after' => 900.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            report: $stubReport,
            wallet: $mockWallet,
            credentials: $stubCredentials
        );
        $service->bet(request: $request);
    }

    public function test_bet_stubWalletWagerInvalidStatus_WalletErrorException()
    {
        $this->expectException(ProviderWalletErrorException::class);

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 1000.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn(['status_code' => 999]);

        $service = $this->makeService(repository: $stubRepository, report: $stubReport, wallet: $stubWallet);
        $service->bet(request: $request);
    }

    public function test_bet_stubWallet_expectedData()
    {
        $expectedData = 200000.00;

        $request = new Request([
            'access_token' => 'testToken',
            'txn_id' => 12345,
            'total_bet' => 10000,
            'game_id' => 'testGameID',
            'ts' => 1704038400
        ]);

        $stubRepository = $this->createMock(Gs5Repository::class);
        $stubRepository->method('getPlayerByToken')
            ->willReturn((object) [
                'play_id' => 'testPlayID',
                'currency' => 'IDR'
            ]);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('balance')
            ->willReturn([
                'credit' => 2100.00,
                'status_code' => 2100
            ]);

        $stubReport = $this->createMock(WalletReport::class);
        $stubReport->method('makeSlotReport')
            ->willReturn(new Report);

        $stubWallet->method('wager')
            ->willReturn([
                'credit_after' => 2000.00,
                'status_code' => 2100
            ]);

        $service = $this->makeService(repository: $stubRepository, report: $stubReport, wallet: $stubWallet);
        $response = $service->bet(request: $request);

        $this->assertSame(expected: $expectedData, actual: $response);
    }
}