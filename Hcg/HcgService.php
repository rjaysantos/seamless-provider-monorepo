<?php

namespace Providers\Hcg;

use Exception;
use Carbon\Carbon;
use Providers\Hcg\HcgApi;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use Providers\Hcg\HcgRepository;
use Providers\Hcg\HcgCredentials;
use Illuminate\Support\Facades\DB;
use Providers\Hcg\DTO\HcgPlayerDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Hcg\Contracts\ICredentials;
use Providers\Hcg\Exceptions\WalletErrorException;
use Providers\Hcg\Exceptions\CannotCancelException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Hcg\Exceptions\InsufficientFundException;
use Providers\Hcg\Exceptions\TransactionAlreadyExistException;
use App\Exceptions\Casino\PlayerNotFoundException;
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

        $extID = $casinoRequest->extID;

        $transaction = $this->repository->getTransactionByExtID(extID: $extID);

        if (is_null($transaction) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        return "{$credentials->getVisualUrl()}/#/order_details/en/{$credentials->getAgentID()}/{$transaction->roundID}";
    }

    private function getPlayerBalance(ICredentials $credentials, string $playID): float
    {
        $walletResponse = $this->wallet->balance(credentials: $credentials, playID: $playID);

        if ($walletResponse['status_code'] !== 2100)
            throw new WalletErrorException;

        return $walletResponse['credit'];
    }

    public function getBalance(Request $request)
    {
        $playerDetails = $this->repository->getPlayerByPlayID(playID: $request->uid);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        return $this->getPlayerBalance(
            credentials: $credentials,
            playID: $request->uid
        ) / $credentials->getCurrencyConversion();
    }

    public function betAndSettle(Request $request): float
    {
        $playerDetails = $this->repository->getPlayerByPlayID(playID: $request->uid);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);
        $transactionID = "{$credentials->getTransactionIDPrefix()}-{$request->orderNo}";

        $transactionDetails = $this->repository->getTransactionByTrxID(transactionID: $transactionID);

        if (is_null($transactionDetails) === false)
            throw new TransactionAlreadyExistException;

        $betAmount = $request->bet * $credentials->getCurrencyConversion();

        if ($this->getPlayerBalance(credentials: $credentials, playID: $request->uid) < $betAmount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $winAmount = $request->win * $credentials->getCurrencyConversion();

            $settleTime = Carbon::createFromTimestamp($request->timestamp, self::PROVIDER_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createSettleTransaction(
                transactionID: $transactionID,
                betAmount: $betAmount,
                winAmount: $winAmount,
                settleTime: $settleTime
            );

            $report = $this->report->makeSlotReport(
                transactionID: $transactionID,
                gameCode: $request->gameCode,
                betTime: $settleTime
            );

            $betAndSettleResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $request->uid,
                currency: $playerDetails->currency,
                wagerTransactionID: "wagerpayout-{$transactionID}",
                wagerAmount: $betAmount,
                payoutTransactionID: "wagerpayout-{$transactionID}",
                payoutAmount: $winAmount,
                report: $report
            );

            if ($betAndSettleResponse['status_code'] !== 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollBack();
            throw new $e;
        }

        return $betAndSettleResponse['credit_after'] / $credentials->getCurrencyConversion();
    }

    private function triggerProviderRequestTimeout(): void
    {
        sleep(self::PROVIDER_REQUEST_TIMEOUT_SECONDS);
    }

    public function cancelBetAndSettle(Request $request): void
    {
        $playerDetails = $this->repository->getPlayerByPlayID(playID: $request->uid);

        if (is_null($playerDetails) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        $transactionDetails = $this->repository->getTransactionByTrxID(
            transactionID: "{$credentials->getTransactionIDPrefix()}-{$request->orderNo}"
        );

        if (is_null($transactionDetails) === false)
            throw new CannotCancelException;

        $this->triggerProviderRequestTimeout();
    }
}