<?php

namespace Providers\Ors;

use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;

class OrsRepository
{
    public function __construct(private Randomizer $randomizer)
    {
    }

    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('ors.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        DB::connection('pgsql_write')
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

        DB::connection('pgsql_write')
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

    public function getTransactionByTrxID(string $transactionID): ?object
    {
        return DB::table('ors.reports')
            ->where('trx_id', $transactionID)
            ->first();
    }

    public function getBetTransactionByTrxID(string $transactionID): ?object
    {
        return DB::table('ors.reports')
            ->where('trx_id', $transactionID)
            ->where('updated_at', null)
            ->first();
    }

    public function getPlayGameByPlayIDToken(string $playID, string $token): ?object
    {
        return DB::table('ors.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->first();
    }

    public function createBetTransaction(string $transactionID, float $betAmount, string $betTime): void
    {
        DB::connection('pgsql_write')
            ->table('ors.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => $betAmount,
                'created_at' => $betTime,
                'updated_at' => null
            ]);
    }

    public function cancelBetTransaction(string $transactionID, string $cancelTme): void
    {
        DB::connection('pgsql_write')
            ->table('ors.reports')
            ->where('trx_id', $transactionID)
            ->where('updated_at', null)
            ->update([
                'updated_at' => $cancelTme
            ]);
    }

    public function settleBetTransaction(string $transactionID, float $winAmount, string $settleTime): void
    {
        DB::connection('pgsql_write')
            ->table('ors.reports')
            ->updateOrInsert(
                ['trx_id' => $transactionID],
                [
                    'win_amount' => $winAmount,
                    'updated_at' => $settleTime
                ]
            );
    }

    public function createBonusTransaction(string $transactionID, float $bonusAmount, string $bonusTime): void
    {
        DB::connection('pgsql_write')
            ->table('ors.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => 0,
                'win_amount' => $bonusAmount,
                'created_at' => $bonusTime,
                'updated_at' => $bonusTime
            ]);
    }
}
