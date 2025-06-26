<?php

namespace Providers\Hcg;

use Exception;
use Carbon\Carbon;
use Providers\Hcg\HcgApi;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use Providers\Hcg\HcgRepository;
use Providers\Hcg\HcgCredentials;
use Illuminate\Support\Facades\DB;
use Providers\Hcg\DTO\HcgRequestDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Hcg\Contracts\ICredentials;
use Providers\Hcg\Exceptions\WalletErrorException;
use Providers\Hcg\Exceptions\CannotCancelException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Hcg\Exceptions\InsufficientFundException;
use Providers\Hcg\Exceptions\TransactionAlreadyExistException;
use App\Exceptions\Casino\PlayerNotFoundException as CasinoPlayerNotFoundException;
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

    public function getLaunchUrl(Request $request): string
    {
        $playerDetails = $this->repository->getPlayerByPlayID(playID: $request->playId);

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $request->currency);

        if (is_null($playerDetails) === true) {
            try {
                DB::connection('pgsql_write')->beginTransaction();

                $this->repository->createPlayer(
                    playID: $request->playId,
                    username: $request->username,
                    currency: $request->currency
                );

                $this->api->userRegistrationInterface(credentials: $credentials, playID: $request->playId);
                DB::connection('pgsql_write')->commit();
            } catch (Exception $e) {
                DB::connection('pgsql_write')->rollBack();
                throw $e;
            }
        }

        return $this->api->userLoginInterface(
            credentials: $credentials,
            playID: $request->playId,
            gameCode: $request->gameId
        );
    }

    public function getVisualUrl(Request $request): string
    {
        $playerDetails = $this->repository->getPlayerByPlayID(playID: $request->play_id);

        if (is_null($playerDetails) === true)
            throw new CasinoPlayerNotFoundException;

        $transactionDetails = $this->repository->getTransactionByTrxID(transactionID: $request->bet_id);

        if (is_null($transactionDetails) === true)
            throw new TransactionNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $playerDetails->currency);

        $transactionIDArray = explode('-', $request->bet_id);
        $transactionID = $transactionIDArray[1] ?? $request->bet_id;

        return "{$credentials->getVisualUrl()}/#/order_details/en/{$credentials->getAgentID()}/{$transactionID}";
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

    public function cancelBetAndSettle(HcgRequestDTO $requestDTO): void
    {
        $player = $this->repository->getPlayerByPlayID(playID: $requestDTO->playID);

        if (is_null($player) === true)
            throw new ProviderPlayerNotFoundException;

        $credentials = $this->credentials->getCredentialsByCurrency(currency: $player->currency);

        $transactionDetails = $this->repository->getTransactionByExtID(
            extID: "wagerpayout-{$credentials->getTransactionIDPrefix()}-{$requestDTO->roundID}"
        );

        if (is_null($transactionDetails) === false)
            throw new CannotCancelException;

        $this->triggerProviderRequestTimeout();
    }
}