<?php

namespace Providers\Gs5;

use Illuminate\Support\Facades\DB;

class Gs5Repository
{
    public function getPlayerByToken(string $token): ?object
    {
        return DB::table('gs5.playgame')
            ->join('gs5.players', 'gs5.playgame.play_id', '=', 'gs5.players.play_id')
            ->where('gs5.playgame.token', $token)
            ->first();
    }

    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('gs5.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function getTransactionByTrxID(string $trxID): ?object
    {
        return DB::table('gs5.reports')
            ->where('trx_id', $trxID)
            ->first();
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        DB::connection('pgsql_write')
            ->table('gs5.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
            ]);
    }

    public function createOrUpdatePlayGame(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('gs5.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE'
                ]
            );
    }

    public function settleTransaction(string $trxID, float $winAmount, string $settleTime): void
    {
        DB::connection('pgsql_write')
            ->table('gs5.reports')
            ->where('trx_id', $trxID)
            ->update([
                'win_amount' => $winAmount,
                'updated_at' => $settleTime
            ]);
    }

    public function createWagerTransaction(
        string $trxID,
        float $betAmount,
        string $transactionDate,
    ): void {
        DB::connection('pgsql_write')
            ->table('gs5.reports')
            ->insert([
                'trx_id' => $trxID,
                'bet_amount' => $betAmount,
                'win_amount' => 0.00,
                'updated_at' => null,
                'created_at' => $transactionDate
            ]);
    }
}
