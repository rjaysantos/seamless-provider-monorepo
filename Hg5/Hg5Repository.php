<?php

namespace Providers\Hg5;

use Illuminate\Support\Facades\DB;
use Providers\Hg5\DTO\Hg5PlayerDTO;
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

    public function createOrUpdatePlayGame(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('hg5.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE'
                ]
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
}
