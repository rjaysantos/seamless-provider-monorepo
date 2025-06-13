<?php

namespace Providers\Ors;

use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;

class OrsRepository
{
    public function __construct(private Randomizer $randomizer) {}

    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::connection('pgsql_report_read')
            ->table('ors.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        DB::connection('pgsql_report_write')
            ->table('ors.players')
            ->insertOrIgnore([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
            ]);
    }

    public function createToken(string $playID): string
    {
        $token = $this->randomizer->createToken();

        DB::connection('pgsql_report_write')
            ->table('ors.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE'
                ]
            );

        return $token;
    }

    public function getTransactionByExtID(string $extID): ?object
    {
        return DB::connection('pgsql_report_read')
            ->table('ors.reports')
            ->where('ext_id', $extID)
            ->first();
    }

    public function getPlayGameByPlayIDToken(string $playID, string $token): ?object
    {
        return DB::connection('pgsql_report_read')
            ->table('ors.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->first();
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
    ): void {
        DB::connection('pgsql_report_write')
            ->table('ors.reports')
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
