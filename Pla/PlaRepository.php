<?php

namespace Providers\Pla;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PlaRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('pla.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function getPlayGameByPlayIDToken(string $playID, string $token): ?object
    {
        return DB::table('pla.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->first();
    }

    public function getTransactionByExtID(string $extID): ?object
    {
        return DB::table('pla.reports')
            ->where('ext_id', $extID)
            ->first();
    }

    public function getTransactionByRefID(string $refID): ?object
    {
        return DB::table('pla.reports')
            ->where('ref_id', $refID)
            ->first();
    }

    public function createPlayer(string $playID, string $currency, string $username): void
    {
        DB::connection('pgsql_write')
            ->table('pla.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency
            ]);
    }

    public function createOrUpdateToken(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('pla.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE'
                ]
            );
    }

    public function deleteToken(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('pla.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->delete();
    }

    public function createTransaction(
        string $trxID,
        float $betAmount,
        float $winAmount,
        string $betTime,
        ?string $settleTime,
        string $refID
    ): void {
        DB::connection('pgsql_write')
            ->table('pla.reports')
            ->insert([
                'trx_id' => $trxID,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'created_at' => $betTime,
                'updated_at' => $settleTime,
                'ref_id' => $refID
            ]);
    }

    public function getBetTransactionByRefID(string $refID): ?object
    {
        return DB::table('pla.reports')
            ->where('ref_id', $refID)
            ->where('updated_at', null)
            ->first();
    }

    public function getBetTransactionByTrxID(string $trxID): ?object
    {
        return DB::table('pla.reports')
            ->where('trx_id', $trxID)
            ->where('updated_at', null)
            ->first();
    }
}
