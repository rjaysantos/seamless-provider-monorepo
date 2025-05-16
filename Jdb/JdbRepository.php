<?php

namespace Providers\Jdb;

use Illuminate\Support\Facades\DB;

class JdbRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('jdb.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function getTransactionByTrxID(string $transactionID): ?object
    {
        return DB::table('jdb.reports')
            ->where('trx_id', $transactionID)
            ->first();
    }

    public function createPlayer(
        string $playID,
        string $username,
        string $currency
    ): void {
        DB::connection('pgsql_write')
            ->table('jdb.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
            ]);
    }

    public function createBetTransaction(
        string $transactionID,
        string $betAmount,
        string $betTime
    ): void {
        DB::connection('pgsql_write')
            ->table('jdb.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => $betAmount,
                'created_at' => $betTime,
                'updated_at' => null,
                'history_id' => null
            ]);
    }

    public function createSettleTransaction(
        string $transactionID,
        float $betAmount,
        float $winAmount,
        string $transactionDate,
        string $historyID
    ): void {
        DB::connection('pgsql_write')
            ->table('jdb.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'updated_at' => $transactionDate,
                'created_at' => $transactionDate,
                'history_id' => $historyID
            ]);
    }

    public function cancelBetTransaction(
        string $transactionID,
        string $cancelTime
    ): void {
        DB::connection('pgsql_write')
            ->table('jdb.reports')
            ->where('trx_id', $transactionID)
            ->update([
                'win_amount' => 0,
                'updated_at' => $cancelTime
            ]);
    }

    public function settleBetTransaction(
        string $transactionID,
        string $historyID,
        string $winAmount,
        string $settleTime
    ): void {
        DB::connection('pgsql_write')
            ->table('jdb.reports')
            ->where('trx_id', $transactionID)
            ->update([
                'win_amount' => $winAmount,
                'updated_at' => $settleTime,
                'history_id' => $historyID
            ]);
    }
}