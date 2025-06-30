<?php

namespace Providers\Ygr;

use Exception;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Ygr\DTO\YgrPlayerDTO;
use Providers\Ygr\DTO\YgrRequestDTO;
use Providers\Ygr\DTO\YgrTransactionDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Ygr\Contracts\ICredentials;
use Providers\Ygr\Exceptions\WalletErrorException;
use Providers\Ygr\Exceptions\TokenNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Ygr\Exceptions\InsufficientFundException;
use Providers\Ygr\Exceptions\TransactionAlreadyExistsException;

class YgrService
{
    public function __construct(
        private YgrRepository $repository,
        private YgrCredentials $credentials,
        private YgrApi $api,
        private IWallet $wallet,
        private WalletReport $walletReport
    ) {}

    public function getLaunchUrl(CasinoRequestDTO $casinoRequest): string
    {
        $player = YgrPlayerDTO::fromPlayRequestDTO(casinoRequestDTO: $casinoRequest);

        $credentials = $this->credentials->getCredentials(currency: $player->currency);

        $this->repository->createOrIgnorePlayer(playerDTO: $player);

        $this->repository->updateOrInsertPlayerTokenAndGameID(playerDTO: $player, gameID: $casinoRequest->gameID);

        return $this->api->launch(credentials: $credentials, playerDTO: $player, language: $casinoRequest->lang);
    }

    public function getBetDetailUrl(CasinoRequestDTO $casinoRequestDTO): string
    {
        $transaction = $this->repository->getTransactionByExtID(extID: $casinoRequestDTO->extID);

        if (is_null($transaction) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentials(currency: $transaction->currency);

        return $this->api->getBetDetailUrl(credentials: $credentials, transactionDTO: $transaction);
    }

    private function getPlayerBalance(ICredentials $credentials, string $playID): float
    {
        $balanceResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($balanceResponse['status_code'] != 2100)
            throw new WalletErrorException;

        return $balanceResponse['credit'];
    }

    public function getPlayerDetails(YgrRequestDTO $requestDTO): object
    {
        $player = $this->repository->getPlayerByToken(token: $requestDTO->token);

        if (is_null($player) === true)
            throw new TokenNotFoundException;

        $credentials = $this->credentials->getCredentials(currency: $player->currency);

        return (object) [
            'credentials' => $credentials,
            'player' => $player,
            'balance' => $this->getPlayerBalance(credentials: $credentials, playID: $player->playID)
        ];
    }

    public function deleteToken(YgrRequestDTO $requestDTO): void
    {
        $player = $this->repository->getPlayerByToken(token: $requestDTO->token);

        if (is_null($player) === true)
            throw new TokenNotFoundException;

        $this->repository->resetPlayerToken(playerDTO: $player);
    }

    public function betAndSettle(YgrRequestDTO $requestDTO): object
    {
        $player = $this->repository->getPlayerByToken(token: $requestDTO->token);

        if (is_null($player) === true)
            throw new TokenNotFoundException;

        $transactionDTO = YgrTransactionDTO::wagerAndPayout(requestDTO: $requestDTO, playerDTO: $player);

        $transaction = $this->repository->getTransactionByExtID(extID: $transactionDTO->extID);

        if (is_null($transaction) === false)
            throw new TransactionAlreadyExistsException;

        $credentials = $this->credentials->getCredentials(currency: $transactionDTO->currency);

        $balance = $this->getPlayerBalance(credentials: $credentials, playID: $transactionDTO->playID);

        if ($balance < $transactionDTO->betAmount)
            throw new InsufficientFundException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $transactionDTO);

            $report = $this->walletReport->makeSlotReport(
                transactionID: $transactionDTO->roundID,
                gameCode: $transactionDTO->gameID,
                betTime: $transactionDTO->dateTime
            );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $transactionDTO->playID,
                currency: $transactionDTO->currency,
                wagerTransactionID: $transactionDTO->extID,
                wagerAmount: $transactionDTO->betAmount,
                payoutTransactionID: $transactionDTO->extID,
                payoutAmount: $transactionDTO->winAmount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new WalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollback();
            throw $e;
        }

        return (object) [
            'balance' => $walletResponse['credit_after'],
            'currency' => $transactionDTO->currency
        ];
    }
}
