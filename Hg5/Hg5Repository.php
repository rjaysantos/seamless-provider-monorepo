<?php

namespace Providers\Hg5;

use App\Repositories\AbstractProviderRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Providers\Hg5\DTO\Hg5PlayerDTO;

class Hg5Repository extends AbstractProviderRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('hg5.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function getPlayerByToken(string $token): ?object
    {
        return DB::table('hg5.playgame')
            ->join('hg5.players', 'hg5.playgame.play_id', '=', 'hg5.players.play_id')
            ->where('hg5.playgame.token', $token)
            ->first();
    }

    public function getTransactionByTrxID(string $trxID): ?object
    {
        return DB::table('hg5.reports')
            ->where('trx_id', $trxID)
            ->first();
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

    public function createOrIgnorePlayer(Hg5PlayerDTO $playerDTO): void
    {
        $this->write->table('hg5.players')
            ->insertOrIgnore([
                'play_id' => $playerDTO->playID,
                'username' => $playerDTO->username,
                'currency' => $playerDTO->currency,
            ]);
    }

    public function updatePlayerToken(Hg5PlayerDTO $playerDTO, string $token): void
    {
        $this->write->table('hg5.players')
            ->where('play_id', $playerDTO->playID)
            ->update(['token' => $token]);
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
}
