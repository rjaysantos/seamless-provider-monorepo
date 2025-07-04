<?php

namespace Providers\Hg5;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Providers\Hg5\DTO\Hg5PlayerDTO;
use Providers\Hg5\DTO\Hg5TransactionDTO;
use App\Repositories\AbstractProviderRepository;

class Hg5Repository extends AbstractProviderRepository
{
    public function getPlayerByPlayID(string $playID): ?Hg5PlayerDTO
    {
        $data = $this->read->table('hg5.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : Hg5PlayerDTO::fromDB(dbData: $data);
    }

    public function getPlayerByToken(string $token): ?Hg5PlayerDTO
    {
        $data = $this->read->table('hg5.players')
            ->where('token', $token)
            ->first();

        return $data == null ? null : Hg5PlayerDTO::fromDB(dbData: $data);
    }

    public function getTransactionByExtID(string $extID): ?Hg5TransactionDTO
    {
        $data = $this->read->table('hg5.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : Hg5TransactionDTO::fromDB(dbData: $data);
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        DB::connection('pgsql_write')
            ->table('hg5.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
            ]);
    }

    public function createOrUpdatePlayer(Hg5PlayerDTO $playerDTO, string $token): void
    {
        $this->write->table('hg5.players')
            ->updateOrInsert(
                [
                    'play_id' => $playerDTO->playID,
                    'username' => $playerDTO->username,
                    'currency' => $playerDTO->currency
                ],
                ['token' => $token]
            );
    }

    public function createWagerAndPayoutTransaction(
        string $trxID,
        float $betAmount,
        float $winAmount,
        string $transactionDate
    ): void {
        DB::connection('pgsql_write')
            ->table('hg5.reports')
            ->insert([
                'trx_id' => $trxID,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'updated_at' => $transactionDate,
                'created_at' => $transactionDate
            ]);
    }

    public function createBetTransaction(
        string $trxID,
        float $betAmount,
        string $betTime,
    ): void {
        DB::connection('pgsql_write')
            ->table('hg5.reports')
            ->insert([
                'trx_id' => $trxID,
                'bet_amount' => $betAmount,
                'win_amount' => 0.00,
                'updated_at' => null,
                'created_at' => $betTime,
            ]);
    }

    public function settleTransaction(
        string $trxID,
        float $winAmount,
        string $settleTime
    ): void {
        DB::connection('pgsql_write')
            ->table('hg5.reports')
            ->where('trx_id', $trxID)
            ->update([
                'win_amount' => $winAmount,
                'updated_at' => $settleTime
            ]);
    }

    public function createTransaction(Hg5TransactionDTO $transactionDTO): void
    {
        $this->write->table('hg5.reports')
            ->insert([
                'ext_id' => $transactionDTO->extID,
                'round_id' => $transactionDTO->roundID,
                'username' => $transactionDTO->username,
                'play_id' => $transactionDTO->playID,
                'web_id' => $transactionDTO->webID,
                'currency' => $transactionDTO->currency,
                'game_code' => $transactionDTO->gameID,
                'bet_amount' => $transactionDTO->betAmount,
                'bet_valid' => $transactionDTO->betValid,
                'bet_winlose' => $transactionDTO->betWinlose,
                'updated_at' => $transactionDTO->dateTime,
                'created_at' => $transactionDTO->dateTime
            ]);
    }
}
