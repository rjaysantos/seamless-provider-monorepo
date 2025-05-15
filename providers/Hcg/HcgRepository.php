<?php

namespace Providers\Hcg;

use Illuminate\Support\Facades\DB;

class HcgRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('hcg.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        DB::connection('pgsql_write')
            ->table('hcg.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
            ]);
    }

    public function getTransactionByTrxID(string $transactionID): ?object
    {
        return DB::table('hcg.reports')
            ->where('trx_id', $transactionID)
            ->first();
    }

    public function createSettleTransaction(
        string $transactionID,
        float $betAmount,
        float $winAmount,
        string $settleTime
    ): void {
        DB::connection('pgsql_write')
            ->table('hcg.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'created_at' => $settleTime,
                'updated_at' => $settleTime,
            ]);
    }
}