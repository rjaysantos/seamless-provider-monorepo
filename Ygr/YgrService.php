<?php

namespace Providers\Ygr;

use Exception;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Contracts\V2\IWallet;
use App\DTO\CasinoRequestDTO;
use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;
use Providers\Ygr\DTO\YgrRequestDTO;
use App\Libraries\Wallet\V2\WalletReport;
use Providers\Ygr\Contracts\ICredentials;
use Providers\Ygr\Exceptions\WalletErrorException;
use Providers\Ygr\Exceptions\TokenNotFoundException;
use App\Exceptions\Casino\TransactionNotFoundException;
use Providers\Ygr\DTO\YgrPlayerDTO;
use Providers\Ygr\Exceptions\InsufficientFundException;
use Providers\Ygr\Exceptions\TransactionAlreadyExistsException;

class YgrService
{
    private const PROVIDER_API_TIMEZONE = 'GMT+8';

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

    public function deleteToken(Request $request): void
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->connectToken);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $this->repository->deletePlayGameByToken(token: $request->connectToken);
    }

    public function betAndSettle(Request $request): object
    {
        $playerData = $this->repository->getPlayerByToken(token: $request->connectToken);

        if (is_null($playerData) === true)
            throw new TokenNotFoundException;

        $transactionData = $this->repository->getTransactionByTrxID(transactionID: $request->roundID);

        if (is_null($transactionData) === false)
            throw new TransactionAlreadyExistsException;

        $credentials = $this->credentials->getCredentials();

        $balance = $this->getPlayerBalance(credentials: $credentials, playID: $playerData->play_id);

        if ($balance < $request->betAmount)
            throw new InsufficientFundException;

        try {
            DB::connection('pgsql_write')->beginTransaction();

            $transactionDate = Carbon::parse($request->wagersTime, self::PROVIDER_API_TIMEZONE)
                ->setTimezone('GMT+8')
                ->format('Y-m-d H:i:s');

            $this->repository->createTransaction(
                transactionID: $request->roundID,
                betAmount: $request->betAmount,
                winAmount: $request->payoutAmount,
                transactionDate: $transactionDate
            );

            $report = $this->walletReport->makeSlotReport(
                transactionID: $request->roundID,
                gameCode: $playerData->status, // gameID inserted from play api
                betTime: $transactionDate
            );

            $walletResponse = $this->wallet->wagerAndPayout(
                credentials: $credentials,
                playID: $playerData->play_id,
                currency: $playerData->currency,
                wagerTransactionID: $request->roundID,
                wagerAmount: $request->betAmount,
                payoutTransactionID: $request->roundID,
                payoutAmount: $request->payoutAmount,
                report: $report
            );

            if ($walletResponse['status_code'] != 2100)
                throw new WalletErrorException;

            DB::connection('pgsql_write')->commit();
        } catch (Exception $e) {
            DB::connection('pgsql_write')->rollback();
            throw $e;
        }

        return (object) [
            'balance' => $walletResponse['credit_after'],
            'currency' => $playerData->currency
        ];
    }
}
