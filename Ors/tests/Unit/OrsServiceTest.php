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
use Providers\Ors\DTO\OrsPlayerDTO;
use Providers\Ors\DTO\OrsRequestDTO;
use Wallet\V1\ProvSys\Transfer\Report;
use Providers\Ors\DTO\OrsTransactionDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Ors\Contracts\ICredentials;
use Providers\Ors\Exceptions\WalletErrorException;
use Providers\Ors\Exceptions\InvalidTokenException;
use Providers\Ors\Exceptions\InvalidPublicKeyException;
use Providers\Ors\Exceptions\InvalidSignatureException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
use Providers\Ors\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException as CasinoTransactionNotFoundException;
use Providers\Ors\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class OrsServiceTest extends TestCase
{
    private function makeService(
        $repository = null,
        $credentials = null,
        $api = null,
        $encryption = null,
        $wallet = null,
        $report = null
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
            walletReport: $report
        );
    }

    public function test_balance_mockWallet_balance()
    {
        $request = new Request([
            'player_id' => 'testPlayID',
            'signature' => 'testSignature',
        ]);

        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayID',
            signature: 'testSignature',
            rawRequest: $request
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayID',
                currency: 'IDR'
            ));

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
            ->with(
                credentials: $stubProviderCredentials,
                playID: 'testPlayID'
            )
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

        $service->balance(requestDTO: $requestDTO);
    }

    public function test_wager_mockWallet_balance()
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
                        'transaction_id' => 'testTransactionID1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayIDu001',
            signature: 'testSignature',
            totalAmount: 150,
            rawRequest: $request,
            transactions: [
                new OrsRequestDTO(
                    gameID: 123,
                    amount: 150,
                    roundID: 'testTransactionID1',
                    dateTime: 1715071526
                )
            ]
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

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
            ->with(credentials: $stubProviderCredentials, playID: 'testPlayIDu001')
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

        $service->wager(requestDTO: $requestDTO);
    }

    public function test_wager_mockReport_makeSlotReport()
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
                        'transaction_id' => 'testTransactionID1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayIDu001',
            signature: 'testSignature',
            totalAmount: 150,
            rawRequest: $request,
            transactions: [
                new OrsRequestDTO(
                    gameID: 123,
                    amount: 150,
                    roundID: 'testTransactionID1',
                    dateTime: 1715071526
                )
            ]
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

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
                transactionID: 'testTransactionID1',
                gameCode: 123,
                betTime: '2024-05-07 16:45:26'
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

        $service->wager(requestDTO: $requestDTO);
    }

    public function test_wager_mockReport_makeArcadeReport()
    {
        $request = new Request(
            query: [
                'player_id' => 'testPlayID',
                'total_amount' => 150,
                'transaction_type' => 'debit',
                'game_id' => 131,
                'round_id' => 'testRoundID',
                'called_at' => 1234567891,
                'records' => [
                    [
                        'transaction_id' => 'testTransactionID1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayIDu001',
            signature: 'testSignature',
            totalAmount: 150,
            rawRequest: $request,
            transactions: [
                new OrsRequestDTO(
                    gameID: 131,
                    amount: 150,
                    roundID: 'testTransactionID1',
                    dateTime: 1715071526
                )
            ]
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

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
                transactionID: 'testTransactionID1',
                gameCode: 131,
                betTime: '2024-05-07 16:45:26'
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

        $service->wager(requestDTO: $requestDTO);
    }

    public function test_wager_mockWallet_wager()
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
                        'transaction_id' => 'testTransactionID1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayIDu001',
            signature: 'testSignature',
            totalAmount: 150,
            rawRequest: $request,
            transactions: [
                new OrsRequestDTO(
                    gameID: 123,
                    amount: 150,
                    roundID: 'testTransactionID1',
                    dateTime: 1715071526
                )
            ]
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

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
                playID: 'testPlayIDu001',
                currency: 'IDR',
                transactionID: 'wager-testTransactionID1',
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

        $service->wager(requestDTO: $requestDTO);
    }

    public function test_cancel_mockWallet_cancel()
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
                        'transaction_id' => 'testTransactionID1',
                        'amount' => 150
                    ]
                ],
                'signature' => 'testSignature'
            ],
            server: [
                'HTTP_KEY' => 'testPublicKey'
            ]
        );

        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayIDu001',
            signature: 'testSignature',
            totalAmount: 150,
            rawRequest: $request,
            transactions: [
                new OrsRequestDTO(
                    gameID: 123,
                    amount: 150,
                    roundID: 'testTransactionID1',
                    dateTime: 1715071526
                )
            ],
            dateTime: 1715071526
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayIDu001',
                currency: 'IDR'
            ));

        $stubRepository->method('getTransactionByExtID')
            ->willReturn(new OrsTransactionDTO(
                extID: 'wager-testTransactionID1',
                roundID: 'testTransactionID1',
                playID: 'testPlayIDu001',
                username: 'testUsername',
                webID: 27,
                currency: 'IDR',
                gameID: 123,
                betValid: 150,
                betAmount: 150,
                dateTime: '2025-06-21 15:36:10',
            ));

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
                transactionID: 'cancel-testTransactionID1',
                amount: 150,
                transactionIDToCancel: 'wager-testTransactionID1'
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

        $service->cancel(requestDTO: $requestDTO);
    }

    public function test_payout_mockWallet_balance()
    {
        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayIDu1',
            amount: 150,
            roundID: 'testTransactionID',
            gameID: 999,
            dateTime: 1735660800,
            signature: 'testSignature',
            rawRequest: new Request()
        );

        $wagerTransaction = new OrsTransactionDTO(
            extID: 'wager-testTransactionID',
            roundID: 'testTransactionID',
            playID: 'testPlayIDu1',
            username: 'testUsername',
            webID: 1,
            currency: 'IDR',
            gameID: 999,
            betAmount: 100,
            betValid: 100,
            betWinlose: 0,
            dateTime: '2025-01-01 00:00:00',
            winAmount: 150
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayIDu1',
                currency: 'IDR'
            ));
        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls($wagerTransaction, null);

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
        $stubWallet->expects($this->once())
            ->method('payout')
            ->with(
                $stubProviderCredentials,
                'testPlayIDu1',
                'IDR',
                'payout-testTransactionID',
                50.0,
                $this->isInstanceOf(Report::class)
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 888.88
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $stubReport
        );

        $service->payout(requestDTO: $requestDTO);
    }

    public function test_payout_mockReport_makeSlotReport()
    {
        $request = new Request();

        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayIDu1',
            amount: 150,
            roundID: 'testTransactionID',
            gameID: 999,
            dateTime: 1735660800,
            signature: 'testSignature',
            rawRequest: $request
        );

        $wagerTransaction = new OrsTransactionDTO(
            extID: 'wager-testTransactionID',
            roundID: 'testTransactionID',
            playID: 'testPlayIDu1',
            username: 'testUsername',
            webID: 1,
            currency: 'IDR',
            gameID: 999,
            betAmount: 100,
            betValid: 100,
            betWinlose: 0,
            dateTime: '2025-01-01 00:00:00',
            winAmount: 150
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayIDu1',
                currency: 'IDR'
            ));
        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls($wagerTransaction, null);

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
                $wagerTransaction->roundID,
                $wagerTransaction->gameID,
                $wagerTransaction->dateTime
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')->willReturn([
            'status_code' => 2100,
            'credit_after' => 500.0
        ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $mockReport
        );

        $service->payout(requestDTO: $requestDTO);
    }

    public function test_payout_mockReport_makeArcadeReport()
    {
        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayIDu1',
            amount: 150,
            roundID: 'testTransactionID',
            gameID: 999,
            dateTime: 1735660800,
            signature: 'testSignature',
            rawRequest: new Request()
        );

        $wagerTransaction = new OrsTransactionDTO(
            extID: 'wager-testTransactionID',
            roundID: 'testTransactionID',
            playID: 'testPlayIDu1',
            username: 'testUsername',
            webID: 1,
            currency: 'IDR',
            gameID: 999,
            betAmount: 100,
            betValid: 100,
            betWinlose: 0,
            dateTime: '2025-01-01 00:00:00',
            winAmount: 150
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayIDu1',
                currency: 'IDR'
            ));
        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls($wagerTransaction, null);

        $stubProviderCredentials = $this->createMock(ICredentials::class);
        $stubProviderCredentials->method('getPublicKey')
            ->willReturn('testPublicKey');
        $stubProviderCredentials->method('getArcadeGameList')
            ->willReturn([999]);

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
                $wagerTransaction->roundID,
                $wagerTransaction->gameID,
                $wagerTransaction->dateTime
            )
            ->willReturn(new Report);

        $stubWallet = $this->createMock(IWallet::class);
        $stubWallet->method('payout')->willReturn([
            'status_code' => 2100,
            'credit_after' => 100.0
        ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $stubWallet,
            report: $mockReport
        );

        $service->payout(requestDTO: $requestDTO);
    }

    public function test_payout_mockWallet_payout()
    {
        $requestDTO = new OrsRequestDTO(
            key: 'testPublicKey',
            playID: 'testPlayIDu1',
            amount: 150,
            roundID: 'testTransactionID',
            gameID: 999,
            dateTime: 1735660800,
            signature: 'testSignature',
            rawRequest: new Request()
        );

        $wagerTransaction = new OrsTransactionDTO(
            extID: 'wager-testTransactionID',
            roundID: 'testTransactionID',
            playID: 'testPlayIDu1',
            username: 'testUsername',
            webID: 1,
            currency: 'IDR',
            gameID: 999,
            betAmount: 100,
            betValid: 100,
            betWinlose: 0,
            dateTime: now()->toDateTimeString(),
            winAmount: 150
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn(new OrsPlayerDTO(
                playID: 'testPlayIDu1',
                currency: 'IDR'
            ));
        $stubRepository->method('getTransactionByExtID')
            ->willReturnOnConsecutiveCalls($wagerTransaction, null);

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
                $stubProviderCredentials,
                'testPlayIDu1',
                'IDR',
                'payout-testTransactionID',
                50.0,
                report: new Report
            )
            ->willReturn([
                'status_code' => 2100,
                'credit_after' => 100.0
            ]);

        $service = $this->makeService(
            repository: $stubRepository,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
            wallet: $mockWallet,
            report: $stubReport
        );

        $service->payout(requestDTO: $requestDTO);
    }

    public function test_bonus_mockWalletReport_makeBonusReport()
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

        $requestDTO = new OrsRequestDTO(
            key: $request->header('key'),
            playID: 'testPlayeru001',
            signature: 'testSignature',
            gameID: 123,
            amount: 1000,
            roundID: 'testTransactionID',
            dateTime: $request->called_at,
            rawRequest: $request
        );

        $playerDTO = new OrsPlayerDTO(
            playID: 'testPlayeru001',
            username: 'testUsername',
            currency: 'IDR',
        );

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

        $stubRepository->method('getTransactionByExtID')
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

        $mockWalletReport = $this->createMock(WalletReport::class);
        $mockWalletReport->expects($this->once())
            ->method('makeBonusReport')
            ->with(
                transactionID: 'testTransactionID',
                gameCode: 123,
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
            report: $mockWalletReport,
            repository: $stubRepository,
            wallet: $stubWallet,
            credentials: $stubCredentials,
            encryption: $stubEncryption,
        );

        $service->bonus(requestDTO: $requestDTO);
    }

    public function test_bonus_mockWallet_bonus()
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

        $requestDTO = new OrsRequestDTO(
            key: $request->header('key'),
            playID: 'testPlayeru001',
            signature: 'testSignature',
            gameID: 123,
            amount: 1000,
            roundID: 'testTransactionID',
            dateTime: $request->called_at,
            rawRequest: $request
        );

        $playerDTO = new OrsPlayerDTO(
            playID: 'testPlayeru001',
            username: 'testUsername',
            currency: 'IDR',
        );

        $report = new Report;

        $stubRepository = $this->createMock(OrsRepository::class);
        $stubRepository->method('getPlayerByPlayID')
            ->willReturn($playerDTO);

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
                playID: 'testPlayeru001',
                currency: 'IDR',
                transactionID: "bonus-testTransactionID",
                amount: 1000,
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

        $service->bonus(requestDTO: $requestDTO);
    }
}
