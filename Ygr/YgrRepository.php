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

    public function getTransactionByExtID(string $extID): ?object
    {
        return DB::table('ygr.reports')
            ->where('ext_id', $extID)
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
        string $transactionDate
    ): void {
        DB::connection('pgsql_report_write')
            ->table('ygr.reports')
            ->insert([
                'ext_id' => $extID,
                'username' => $username,
                'play_id' => $playID,
                'web_id' => $this->getWebID(playID: $playID),
                'currency' => $currency,
                'game_code' => $gameCode,
                'bet_amount' => $betAmount,
                'bet_valid' => $betAmount,
                'bet_winlose' => $betWinlose,
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
