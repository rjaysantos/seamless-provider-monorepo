<?php

namespace Providers\Ors;

use Exception;
use Carbon\Carbon;
use Providers\Ors\OrsApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Ors\OgSignature;
use Providers\Ors\OrsRepository;
use Providers\Ors\OrsCredentials;
use Illuminate\Support\Facades\DB;
use Providers\Ors\DTO\OrsPlayerDTO;
use Providers\Ors\DTO\OrsRequestDTO;
use Providers\Ors\DTO\OrsTransactionDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Ors\Contracts\ICredentials;
use Providers\Ors\Exceptions\WalletErrorException;
use Providers\Ors\Exceptions\InvalidTokenException;
use Providers\Ors\Exceptions\InsufficientFundException;
use Providers\Ors\Exceptions\InvalidPublicKeyException;
use Providers\Ors\Exceptions\InvalidSignatureException;
use Providers\Ors\Exceptions\TransactionAlreadyExistsException;
use App\Exceptions\Casino\PlayerNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Ors\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;
use Providers\Ors\Exceptions\TransactionNotFoundException as ProviderTransactionNotFoundException;

class OrsService
{
    private const PROVIDER_API_TIMEZONE = 'GMT+8';

    public function __construct(
        private OrsRepository $repository,
        private OrsCredentials $credentials,
        private OrsApi $api,
        private OgSignature $encryption,
        private IWallet $wallet,
        private WalletReport $walletReport
    ) {}

    public function getLaunchUrl(CasinoRequestDTO $casinoRequest): string
    {
        $player = OrsPlayerDTO::fromPlayRequestDTO(casinoRequestDTO: $casinoRequest);

        $this->repository->createOrUpdatePlayer(playerDTO: $player);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        return $this->api->enterGame(credentials: $credentials, playerDTO: $player, casinoRequest: $casinoRequest);
    }

    public function getBetDetailUrl(CasinoRequestDTO $casinoRequest): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $casinoRequest->playID);

        if (is_null($player) === true)
            throw new PlayerNotFoundException;

        $transaction = $this->repository->getTransactionByExtID(extID: $casinoRequest->extID);

        if (is_null($transaction) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        return $this->api->getBettingRecords(credentials: $credentials, transactionDTO: $transaction);
    }

    private function verifyPlayerAccess(OrsRequestDTO $requestDTO, ICredentials $credentials): void
    {
        if ($requestDTO->key !== $credentials->getPublicKey())
            throw new InvalidPublicKeyException;

        if ($this->encryption->isSignatureValid(request: $requestDTO->rawRequest, credentials: $credentials) === false)
            throw new InvalidSignatureException;
    }

    private function getPlayerDetails(OrsRequestDTO $requestDTO): object
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        return $player;
    }

    private function getPlayerBalance(ICredentials $credentials, OrsPlayerDTO $playerDTO): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $playerDTO->playID);

        if ($walletResponse['status_code'] != 2100)
            throw new WalletErrorException;

        return $walletResponse['credit'];
    }

    public function authenticate(OrsRequestDTO $requestDTO)
    {
        $player = $this->getPlayerDetails(requestDTO: $requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->verifyPlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        if ($player->token !== $requestDTO->token)
            throw new InvalidTokenException;
    }

    public function balance(OrsRequestDTO $requestDTO): object
    {
        $player = $this->getPlayerDetails(requestDTO: $requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->verifyPlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        $balance = $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        return (object) [
            'balance' => $balance,
            'player' => $player
        ];
    }

    public function wager(OrsRequestDTO $requestDTO): float
    {
        $player = $this->getPlayerDetails(requestDTO: $requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->verifyPlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        $balance = $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        if ($balance < $requestDTO->totalAmount)
            throw new InsufficientFundException;

        foreach ($requestDTO->transactions as $transaction) {
            $existingTransaction = $this->repository->getTransactionByExtID(extID: "wager-{$transaction->roundID}");

            if (is_null($existingTransaction) === false)
                throw new TransactionAlreadyExistsException;
        }

        foreach ($requestDTO->transactions as $transaction) {
            $wagerTransactionDTO = OrsTransactionDTO::wager(
                extID: "wager-{$transaction->roundID}",
                requestDTO: $transaction,
                playerDTO: $player
            );

            try {
                $this->repository->beginTransaction();

                $this->repository->createTransaction(transactionDTO: $wagerTransactionDTO);

                if (in_array($wagerTransactionDTO->gameID, $credentials->getArcadeGameList()) === true)
                    $report = $this->walletReport->makeArcadeReport(
                        transactionID: $wagerTransactionDTO->roundID,
                        gameCode: $wagerTransactionDTO->gameID,
                        betTime: $wagerTransactionDTO->dateTime
                    );
                else
                    $report = $this->walletReport->makeSlotReport(
                        transactionID: $wagerTransactionDTO->roundID,
                        gameCode: $wagerTransactionDTO->gameID,
                        betTime: $wagerTransactionDTO->dateTime
                    );

                $walletResponse = $this->wallet->wager(
                    credentials: $credentials,
                    playID: $wagerTransactionDTO->playID,
                    currency: $wagerTransactionDTO->currency,
                    transactionID: $wagerTransactionDTO->extID,
                    amount: $wagerTransactionDTO->betAmount,
                    report: $report
                );

                if ($walletResponse['status_code'] !== 2100)
                    throw new WalletErrorException;

                $this->repository->commit();
            } catch (Exception $e) {
                $this->repository->rollback();
                throw $e;
            }
        }

        return $walletResponse['credit_after'];
    }

    public function rollback(Request $request): float
    {
        $playerData = $this->getPlayerDetails(request: $request);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerData->currency);

        $this->verifyPlayerAccess(request: $request, credentials: $credentials);

        foreach ($request->records as $record) {
            $betTransaction = $this->repository->getBetTransactionByTrxID(transactionID: $record['transaction_id']);

            if (is_null($betTransaction) === true)
                throw new ProviderTransactionNotFoundException;
        }

        foreach ($request->records as $record) {
            try {
                DB::connection('pgsql_write')->beginTransaction();

                $this->repository->cancelBetTransaction(
                    transactionID: $record['transaction_id'],
                    cancelTme: Carbon::parse($request->called_at, self::PROVIDER_API_TIMEZONE)
                        ->setTimezone('GMT+8')
                        ->format('Y-m-d H:i:s')
                );

                $walletResponse = $this->wallet->cancel(
                    credentials: $credentials,
                    transactionID: "cancelBet-{$record['transaction_id']}",
                    amount: $record['amount'],
                    transactionIDToCancel: "wager-{$record['transaction_id']}"
                );

                if ($walletResponse['status_code'] !== 2100)
                    throw new WalletErrorException;

                DB::connection('pgsql_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_write')->rollBack();
                throw $e;
            }
        }

        return $walletResponse['credit_after'];
    }

    public function settle(OrsRequestDTO $requestDTO): float
    {
        $player = $this->getPlayerDetails($requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->verifyPlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        $wagerTransaction = $this->repository->getTransactionByExtID(extID: "wager-{$requestDTO->roundID}");

        if (is_null($wagerTransaction) === true)
            throw new ProviderTransactionNotFoundException;

        $payoutTransactionDTO = OrsTransactionDTO::payout(
            extID: "payout-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            wagerTransactionDTO: $wagerTransaction
        );

        $existingPayoutTransaction = $this->repository->getTransactionByExtID(extID: $payoutTransactionDTO->extID);

        if (is_null($existingPayoutTransaction) === false)
            return $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $payoutTransactionDTO);

            if (in_array($payoutTransactionDTO->gameID, $credentials->getArcadeGameList()) === true)
                $report = $this->walletReport->makeArcadeReport(
                    transactionID: $payoutTransactionDTO->roundID,
                    gameCode: $payoutTransactionDTO->gameID,
                    betTime: $payoutTransactionDTO->dateTime
                );
            else
                $report = $this->walletReport->makeSlotReport(
                    transactionID: $payoutTransactionDTO->roundID,
                    gameCode: $payoutTransactionDTO->gameID,
                    betTime: $payoutTransactionDTO->dateTime
                );

            $walletResponse = $this->wallet->payout(
                credentials: $credentials,
                playID: $payoutTransactionDTO->playID,
                currency: $payoutTransactionDTO->currency,
                transactionID: $payoutTransactionDTO->extID,
                amount: $payoutTransactionDTO->betWinlose,
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }

    public function bonus(OrsRequestDTO $requestDTO): float
    {
        $player = $this->getPlayerDetails(requestDTO: $requestDTO);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $this->verifyPlayerAccess(requestDTO: $requestDTO, credentials: $credentials);

        $bonusTransactionDTO = OrsTransactionDTO::bonus(
            extID: "bonus-{$requestDTO->roundID}",
            requestDTO: $requestDTO,
            playerDTO: $player
        );

        $existingBonusTransaction = $this->repository->getTransactionByExtID(extID: $bonusTransactionDTO->extID);

        if (is_null($existingBonusTransaction) === false)
            throw new TransactionAlreadyExistsException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $bonusTransactionDTO);

            $report = $this->walletReport->makeBonusReport(
                transactionID: $bonusTransactionDTO->roundID,
                gameCode: $bonusTransactionDTO->gameID,
                betTime: $bonusTransactionDTO->dateTime
            );

            $walletResponse = $this->wallet->bonus(
                credentials: $credentials,
                playID: $bonusTransactionDTO->playID,
                currency: $bonusTransactionDTO->currency,
                transactionID: $bonusTransactionDTO->extID,
                amount: $bonusTransactionDTO->winAmount,
                report: $report
            );

            if ($walletResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return $walletResponse['credit_after'];
    }
}
