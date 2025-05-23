<?php

namespace Providers\Sbo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Providers\Sbo\Contracts\ISboSportsbookDetails;

class SboRepository
{
    private const ACTIVE = 1;
    private const INACTIVE = 0;

    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('sbo.players')
            ->where('play_id', $playID)
            ->get()
            ->first();
    }

    public function createPlayer(string $playID, string $currency, string $ip): void
    {
        $dateTimeNow = Carbon::now()->format('Y-m-d H:i:s');

        DB::connection('pgsql_write')->table('sbo.players')
            ->insertOrIgnore([
                'play_id' => $playID,
                'username' => "sbo_{$playID}",
                'currency' => $currency,
                'game' => 0,
                'ip_address' => $ip,
                'created_at' => $dateTimeNow,
                'updated_at' => $dateTimeNow
            ]);
    }

    public function getTransactionByTrxID(string $trxID): ?object
    {
        return DB::table('sbo.reports')
            ->where('trx_id', $trxID)
            ->orderBy('id', 'desc')
            ->get()
            ->first();
    }

    private function getWebID(string $playID): int
    {
        $explodedPlayID = explode('u', $playID);
        return (int) end($explodedPlayID);
    }

    public function createTransaction(
        string $betID,
        string $trxID,
        string $playID,
        string $currency,
        float $betAmount,
        float $payoutAmount,
        string $betTime,
        string $flag,
        ISboSportsbookDetails $sportsbookDetails
    ): void {
        DB::connection('pgsql_write')->table('sbo.reports')
            ->insert([
                'bet_id' => $betID,
                'trx_id' => $trxID,
                'play_id' => $playID,
                'web_id' => $this->getWebID($playID),
                'currency' => $currency,
                'bet_amount' => $betAmount,
                'payout_amount' => $payoutAmount,
                'bet_time' => $betTime,
                'bet_choice' => $sportsbookDetails->getBetChoice(),
                'game_code' => $sportsbookDetails->getGameCode(),
                'sports_type' => $sportsbookDetails->getSportsType(),
                'event' => $sportsbookDetails->getEvent(),
                'match' => $sportsbookDetails->getMatch(),
                'hdp' => $sportsbookDetails->getHdp(),
                'odds' => $sportsbookDetails->getOdds(),
                'result' => $sportsbookDetails->getResult(),
                'flag' => $flag,
                'status' => self::ACTIVE,
            ]);
    }

    public function inactiveTransaction(string $trxID)
    {
        DB::connection('pgsql_write')->table('sbo.reports')
            ->where('trx_id', $trxID)
            ->update(['status' => self::INACTIVE]);
    }

    public function getRunningCount(string $trxID): int
    {
        return DB::table('sbo.reports')
            ->where('trx_id', $trxID)
            ->where('flag', 'running')
            ->count();
    }

    public function getVoidedCount(string $trxID): int
    {
        return DB::table('sbo.reports')
            ->where('trx_id', $trxID)
            ->where('flag', 'void')
            ->count();
    }

    public function getRollbackCount(string $trxID): int
    {
        return  DB::table('sbo.reports')
            ->where('trx_id', $trxID)
            ->whereIn('flag', ['running', 'rollback'])
            ->count();
    }
}
