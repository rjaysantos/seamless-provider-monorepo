<?php

namespace Providers\Bes;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BesRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('bes.players')
            ->where('play_id', $playID)
            ->get()
            ->first();
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        $dateTimeNow = Carbon::now()->format('Y-m-d H:i:s');

        DB::connection('pgsql_write')->table('bes.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
                'created_at' => $dateTimeNow,
                'updated_at' => $dateTimeNow
            ]);
    }

    public function getTransactionByTrxID(string $transactionID): ?object
    {
        return DB::table('bes.reports')
            ->where('trx_id', $transactionID)
            ->get()
            ->first();
    }

    public function createTransaction(string $transactionID, float $betAmount, float $winAmount): void
    {
        $dateTimeNow = Carbon::now()->format('Y-m-d H:i:s');

        DB::connection('pgsql_write')->table('bes.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'created_at' => $dateTimeNow,
                'updated_at' => $dateTimeNow,
            ]);
    }

    public function updateTransactionToSettle(string $transactionID, float $winAmount): void
    {
        $dateTimeNow = Carbon::now()->format('Y-m-d H:i:s');

        DB::connection('pgsql_write')->table('bes.reports')
            ->where('trx_id', $transactionID)
            ->update([
                'win_amount' => $winAmount,
                'updated_at' => $dateTimeNow,
            ]);
    }
}
