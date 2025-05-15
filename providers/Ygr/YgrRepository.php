<?php

namespace Providers\Ygr;

use Illuminate\Support\Facades\DB;

class YgrRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('ygr.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function getPlayerByToken(string $token): ?object
    {
        return DB::table('ygr.playgame')
            ->join('ygr.players', 'ygr.playgame.play_id', '=', 'ygr.players.play_id')
            ->where('ygr.playgame.token', $token)
            ->first();
    }

    public function getTransactionByTrxID(string $transactionID): ?object
    {
        return DB::table('ygr.reports')
            ->where('trx_id', $transactionID)
            ->first();
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        DB::connection('pgsql_write')
            ->table('ygr.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency
            ]);
    }

    public function createOrUpdatePlayGame(string $playID, string $token, string $gameID): void
    {
        DB::connection('pgsql_write')
            ->table('ygr.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE',
                    'status' => $gameID // saving GameID to status for verifyToken
                ]
            );
    }

    public function createTransaction(
        string $transactionID,
        float $betAmount,
        float $winAmount,
        string $transactionDate
    ): void {
        DB::connection('pgsql_write')
            ->table('ygr.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'updated_at' => $transactionDate,
                'created_at' => $transactionDate
            ]);
    }

    public function deletePlayGameByToken(string $token): void
    {
        DB::connection('pgsql_write')
            ->table('ygr.playgame')
            ->where('token', $token)
            ->delete();
    }
}