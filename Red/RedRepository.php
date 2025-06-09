<?php

namespace Providers\Red;

use Illuminate\Support\Facades\DB;

class RedRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('red.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function getPlayerByUserIDProvider(int $userIDProvider): ?object
    {
        return DB::table('red.players')
            ->where('user_id_provider', $userIDProvider)
            ->first();
    }

    public function getTransactionByExtID(string $transactionID): ?object
    {
        return DB::table('red.reports')
            ->where('ext_id', $transactionID)
            ->first();
    }

    public function createPlayer(string $playID, string $currency, int $userIDProvider): void
    {
        DB::connection('pgsql_write')
            ->table('red.players')
            ->insert([
                'play_id' => $playID,
                'username' => $playID,
                'currency' => $currency,
                'user_id_provider' => $userIDProvider
            ]);
    }

    public function createTransaction(string $transactionID, float $betAmount, string $transactionDate): void
    {
        DB::connection('pgsql_write')
            ->table('red.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => $betAmount,
                'win_amount' => 0,
                'updated_at' => null,
                'created_at' => $transactionDate
            ]);
    }

    public function settleTransaction(string $transactionID, float $winAmount, string $transactionDate): void
    {
        DB::connection('pgsql_write')
            ->table('red.reports')
            ->where('ext_id', $transactionID)
            ->update([
                'win_amount' => $winAmount,
                'updated_at' => $transactionDate
            ]);
    }

    public function createBonusTransaction(string $transactionID, float $bonusAmount, string $transactionDate): void
    {
        DB::connection('pgsql_write')
            ->table('red.reports')
            ->insert([
                'ext_id' => $transactionID,
                'bet_amount' => 0,
                'win_amount' => $bonusAmount,
                'updated_at' => $transactionDate,
                'created_at' => $transactionDate
            ]);
    }
}
