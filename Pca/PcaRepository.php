<?php

namespace Providers\Pca;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PcaRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('pca.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function createPlayer(string $playID, string $currency, string $username): void
    {
        DB::connection('pgsql_write')
            ->table('pca.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency
            ]);
    }

    public function createOrUpdateToken(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('pca.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE'
                ]
            );
    }

    public function getTransactionByBetID(string $betID): ?object
    {
        return DB::table('pca.reports')
            ->where('bet_id', $betID)
            ->first();
    }

    public function getTransactionByRefID(string $refID): ?object
    {
        return DB::table('pca.reports')
            ->where('ref_id', $refID)
            ->first();
    }

    public function getPlayGameByPlayIDToken(string $playID, string $token): ?object
    {
        return DB::table('pca.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->first();
    }

    public function deleteToken(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('pca.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->delete();
    }

    public function getBetTransactionByRefID(string $refID): ?object
    {
        return DB::table('pca.reports')
            ->where('ref_id', $refID)
            ->where('status', 'WAGER')
            ->first();
    }

    public function getBetTransactionByBetID(string $betID): ?object
    {
        return DB::table('pca.reports')
            ->where('bet_id', $betID)
            ->where('status', 'WAGER')
            ->first();
    }

    public function createTransaction(
        string $playID, 
        string $currency,
        string $gameCode,
        string $betID,
        string $betAmount, 
        string $winAmount,
        string $betTime,
        string $status,
        string $refID,
    ): void {
        DB::connection('pgsql_write')
            ->table(table: 'pca.reports')
            ->insert([
                'play_id' => $playID,
                'currency' => $currency,
                'game_code' => $gameCode,
                'bet_choice' => '-',
                'bet_id' => $betID,
                'wager_amount' => $betAmount,
                'payout_amount' => $winAmount,
                'bet_time' => $betTime,
                'status' => $status,
                'ref_id' => $refID
            ]);
    }
}