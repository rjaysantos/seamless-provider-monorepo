<?php

namespace Providers\Sab;

use Illuminate\Support\Facades\DB;
use App\Contracts\V2\ISportsbookDetails;

class SabRepository
{
    private const ACTIVE = 1;
    private const INACTIVE = 0;

    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('sab.players')
            ->where('play_id', $playID)
            ->get()
            ->first();
    }

    public function createPlayer(string $playID, string $currency, string $username): void
    {
        DB::connection('pgsql_write')->table('sab.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
                'game' => 0
            ]);
    }

    public function getTransactionByTrxID(string $trxID): ?object
    {
        return DB::table('sab.reports')
            ->where('trx_id', $trxID)
            ->where('status', self::ACTIVE)
            ->get()
            ->first();
    }

    public function getPlayerByUsername(string $username): ?object
    {
        return DB::table('sab.players')
            ->where('username', $username)
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
        string $playID,
        string $currency,
        string $trxID,
        float $betAmount,
        float $payoutAmount,
        string $betDate,
        ?string $ip,
        string $flag,
        ISportsbookDetails $sportsbookDetails
    ): void {

        DB::connection('pgsql_write')->table('sab.reports')
            ->where('trx_id', $trxID)
            ->update(['status' => self::INACTIVE]);

        DB::connection('pgsql_write')->table('sab.reports')->insert([
            'bet_id' => $betID,
            'trx_id' => $trxID,
            'play_id' => $playID,
            'web_id' => $this->getWebID($playID),
            'currency' => $currency,
            'bet_amount' => $betAmount,
            'payout_amount' => $payoutAmount,
            'bet_time' => $betDate,
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
            'ip_address' => $ip
        ]);
    }

    public function getAllRunningTransactions(
        ?int $webID,
        ?string $currency,
        ?int $start,
        ?int $length
    ): object {
        $query = DB::table('sab.reports')
            ->where('web_id', $webID)
            ->where('currency', $currency)
            ->where('flag', 'running')
            ->where('status', self::ACTIVE)
            ->orderBy('created_at', 'desc');

        if (is_null($start) === false)
            $query->offset($start);

        if (is_null($length) === false)
            $query->limit($length);

        return (object)[
            'totalCount' => $query->count(),
            'data' => $query->get()
        ];
    }

    public function getWaitingBetAmountByPlayID(string $playID): float
    {
        return DB::table('sab.reports')
            ->where('play_id', $playID)
            ->where('flag', 'waiting')
            ->where('status', self::ACTIVE)
            ->sum('bet_amount');
    }
}
