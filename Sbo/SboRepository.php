<?php

namespace Providers\Sbo;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $dateTimeNow =  Carbon::now()->format('Y-m-d H:i:s');

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
        string $betTime,
        string $flag,
        object $sportsbookDetails
    ): void {
        DB::connection('pgsql_write')->table('sbo.reports')
            ->insert([
                'bet_id' => $betID,
                'trx_id' => $trxID,
                'play_id' => $playID,
                'web_id' => $this->getWebID($playID),
                'currency' => $currency,
                'bet_amount' => $betAmount,
                'payout_amount' => 0,
                'bet_time' => $betTime,
                'bet_choice' => $sportsbookDetails->betChoice,
                'game_code' => $sportsbookDetails->gameCode,
                'sports_type' => $sportsbookDetails->sportsType,
                'event' => $sportsbookDetails->event,
                'match' => $sportsbookDetails->match,
                'hdp' => $sportsbookDetails->hdp,
                'odds' => $sportsbookDetails->odds,
                'result' => $sportsbookDetails->result,
                'flag' => $flag,
                'status' => '1',
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
        return  DB::table('sbo.reports')
            ->where('trx_id', $trxID)
            ->where('flag', 'running')
            ->count();
    }

    public function getRollbackCount(string $trxID): int
    {
        return  DB::table('sbo.reports')
            ->where('trx_id', $trxID)
            ->whereIn('flag', ['running', 'rollback'])
            ->count();
    }

    public function createRollbackTransaction(
        string $trxID,
        string $betID,
        object $transactionData
    ): void {

        $this->inactiveTransaction(trxID: $trxID);

        DB::connection('pgsql_write')->table('sbo.reports')
            ->insert([
                'bet_id' => $betID,
                'trx_id' => $trxID,
                'play_id' => $transactionData->play_id,
                'web_id' => $transactionData->web_id,
                'currency' => $transactionData->currency,
                'bet_amount' => $transactionData->bet_amount,
                'payout_amount' => 0,
                'bet_time' => $transactionData->bet_time,
                'bet_choice' => $transactionData->bet_choice,
                'game_code' => $transactionData->game_code,
                'sports_type' => $transactionData->sports_type,
                'event' => $transactionData->event,
                'match' => $transactionData->match,
                'hdp' => $transactionData->hdp,
                'odds' => $transactionData->odds,
                'result' => '-',
                'flag' => 'rollback',
                'status' => self::ACTIVE,
            ]);
    }
}
