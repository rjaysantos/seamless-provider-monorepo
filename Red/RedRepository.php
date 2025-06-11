<?php

namespace Providers\Red;

use Illuminate\Support\Facades\DB;

class RedRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::connection('pgsql_report_read')
            ->table('red.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function getPlayerByUserIDProvider(int $userIDProvider): ?object
    {
        return DB::connection('pgsql_report_read')
            ->table('red.players')
            ->where('user_id_provider', $userIDProvider)
            ->first();
    }

    public function getTransactionByExtID(string $extID): ?object
    {
        return DB::connection('pgsql_report_read')
            ->table('red.reports')
            ->where('ext_id', $extID)
            ->first();
    }

    public function createPlayer(string $playID, string $currency, int $userIDProvider): void
    {
        DB::connection('pgsql_report_write')
            ->table('red.players')
            ->insert([
                'play_id' => $playID,
                'username' => $playID,
                'currency' => $currency,
                'user_id_provider' => $userIDProvider
            ]);
    }

    private function getWebID(string $playID)
    {
        if (preg_match_all('/u(\d+)/', $playID, $matches)) {
            $lastNumber = end($matches[1]);
            return $lastNumber;
        }
    }

    public function createTransaction(
        string $extID,
        string $playID,
        string $username,
        string $currency,
        string $gameCode,
        float $betAmount,
        float $betWinlose,
        string $transactionDate,
    ): void
    {
        DB::connection('pgsql_report_write')
            ->table('red.reports')
            ->insert([
                'ext_id' => $extID,
                'username' => $username,
                'play_id' => $playID,
                'web_id' => $this->getWebID($playID),
                'currency' => $currency,
                'game_code' => $gameCode,
                'bet_amount' => $betAmount,
                'bet_valid' => $betAmount,
                'bet_winlose' => $betWinlose,
                'updated_at' => $transactionDate,
                'created_at' => $transactionDate
            ]);
    }
}