<?php

namespace Providers\Hcg;

use Exception;
use Carbon\Carbon;
use Providers\Hcg\HcgApi;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Hcg\HcgRepository;
use Providers\Hcg\HcgCredentials;
use Illuminate\Support\Facades\DB;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Hcg\Contracts\ICredentials;
use Providers\Hcg\Exceptions\WalletErrorException;
use Providers\Hcg\Exceptions\CannotCancelException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Hcg\Exceptions\InsufficientFundException;
use Providers\Hcg\Exceptions\TransactionAlreadyExistException;
use App\Exceptions\Casino\PlayerNotFoundException;
use Providers\Hcg\DTO\HcgRequestDTO;
use Providers\Hcg\DTO\HcgPlayerDTO;
use Providers\Hcg\DTO\HcgTransactionDTO;
use Providers\Hcg\Exceptions\PlayerNotFoundException as ProviderPlayerNotFoundException;

class HcgService
{
    private const PROVIDER_TIMEZONE = 'GMT+8';

    private const PROVIDER_REQUEST_TIMEOUT_SECONDS = 15;

    public function __construct(
        private HcgRepository $repository,
        private HcgCredentials $credentials,
        private HcgApi $api,
        private IWallet $wallet,
        private WalletReport $report
    ) {
    }

    public function getLaunchUrl(CasinoRequestDTO $casinoRequest): string
    {
        $player = $this->repository->getPlayerByPlayID(playID: $casinoRequest->playID);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $casinoRequest->currency);

        if (is_null($player) === true) {
            $player = HcgPlayerDTO::fromPlayRequestDTO(casinoRequestDTO: $casinoRequest);

            try {
                $this->repository->beginTransaction();

                $this->repository->createPlayer(playerDTO: $player);

                $this->api->userRegistrationInterface(credentials: $credentials, playID: $player->playID);

                $this->repository->commit();
            } catch (Exception $e) {
                $this->repository->rollBack();
                throw $e;
            }
        }

        return $this->api->userLoginInterface(
            credentials: $credentials,
            playID: $player->playID,
            gameCode: $casinoRequest->gameID
        );
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

        return "{$credentials->getVisualUrl()}/#/order_details/en/{$credentials->getAgentID()}/{$transaction->roundID}";
    }

    private function getPlayerBalance(ICredentials $credentials, HcgPlayerDTO $playerDTO): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $playerDTO->playID);

        if ($walletResponse['status_code'] !== 2100)
            throw new WalletErrorException;

        return $walletResponse['credit'];
    }

    public function getBalance(HcgRequestDTO $requestDTO)
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $balance = $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        return $balance / $credentials->getCurrencyConversion();
    }

    public function betAndSettle(HcgRequestDTO $requestDTO): float
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $wagerPayoutTransactionDTO = HcgTransactionDTO::wager(
            requestDTO: $requestDTO,
            playerDTO: $player,
            betAmount: $requestDTO->betAmount * $credentials->getCurrencyConversion(),
            winAmount: $requestDTO->winAmount * $credentials->getCurrencyConversion()
        );

        $existingTransactionData = $this->repository->getTransactionByExtID(extID: $wagerPayoutTransactionDTO->extID);

        if (is_null($existingTransactionData) === false)
            throw new TransactionAlreadyExistException;

        $balance = $this->getPlayerBalance(credentials: $credentials, playerDTO: $player);

        if ($balance < $wagerPayoutTransactionDTO->betAmount)
            throw new InsufficientFundException;

        try {
            $this->repository->beginTransaction();

            $this->repository->createTransaction(transactionDTO: $wagerPayoutTransactionDTO);
            
            $report = $this->report->makeSlotReport(
                transactionID: $wagerPayoutTransactionDTO->roundID,
                gameCode: $wagerPayoutTransactionDTO->gameID,
                betTime: $wagerPayoutTransactionDTO->dateTime
            );

            $betAndSettleResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $wagerPayoutTransactionDTO->playID,
                currency: $wagerPayoutTransactionDTO->currency,
                wagerTransactionID: $wagerPayoutTransactionDTO->extID,
                wagerAmount: $wagerPayoutTransactionDTO->betAmount,
                payoutTransactionID: $wagerPayoutTransactionDTO->extID,
                payoutAmount: $wagerPayoutTransactionDTO->winAmount,
                report: $report
            );

            if ($betAndSettleResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            $this->repository->commit();
        } catch (Exception $e) {
            $this->repository->rollBack();
            throw $e;
        }

        return $betAndSettleResponse['credit_after'] / $credentials->getCurrencyConversion();
    }

    private function triggerProviderRequestTimeout(): void
    {
        sleep(self::PROVIDER_REQUEST_TIMEOUT_SECONDS);
    }

    public function cancelBetAndSettle(HcgRequestDTO $requestDTO): void
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $transactionDetails = $this->repository->getTransactionByExtID(
            extID: "wagerpayout-{$requestDTO->roundID}"
        );

        if (is_null($transactionDetails) === false)
            throw new CannotCancelException;

        $this->triggerProviderRequestTimeout();
    }
}