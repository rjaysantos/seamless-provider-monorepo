<?php

namespace Providers\Pca;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PcaRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('pca.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function createPlayer(string $playID, string $currency, string $username): void
    {
        DB::connection('pgsql_write')
            ->table('pca.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency
            ]);
    }

    public function createOrUpdateToken(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('pca.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE'
                ]
            );
    }

    public function getTransactionByBetID(string $betID): ?object
    {
        return DB::table('pca.reports')
            ->where('bet_id', $betID)
            ->first();
    }

    public function getPlayGameByPlayIDToken(string $playID, string $token): ?object
    {
        return DB::table('pca.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->first();
    }

    public function deleteToken(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('pca.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->delete();
    }

    public function getTransactionByTransactionIDRefID(string $transactionID, string $refID): ?object
    {
        return DB::table('pca.reports')
            ->where('bet_id', $transactionID)
            ->where('ref_id', $refID)
            ->first();
    }

    public function getBetTransactionByTransactionID(string $transactionID): ?object
    {
        return DB::table('pca.reports')
            ->where('bet_id', $transactionID)
            ->where('status', 'WAGER')
            ->first();
    }

    public function getBetTransactionByTransactionIDRefID(string $transactionID, string $refID): ?object
    {
        return DB::table('pca.reports')
            ->selectRaw('wager_amount AS bet_amount, bet_time AS created_at, ref_id')
            ->where('bet_id', $transactionID)
            ->where('status', 'WAGER')
            ->where('ref_id', $refID)
            ->first();
    }

    public function getRefundTransactionByTransactionIDRefID(string $transactionID, string $refID): ?object
    {
        return DB::table('pca.reports')
            ->where('bet_id', $transactionID)
            ->where('status', 'REFUND')
            ->where('ref_id', "R-{$refID}")
            ->first();
    }

    public function createTransaction(
        string $playID, 
        string $currency,
        string $gameCode,
        string $betID,
        string $betAmount, 
        string $winAmount,
        string $betTime,
        string $status,
        string $refID,
    ): void {
        DB::connection('pgsql_write')
            ->table(table: 'pca.reports')
            ->insert([
                'play_id' => $playID,
                'currency' => $currency,
                'game_code' => $gameCode,
                'bet_choice' => '-',
                'bet_id' => $betID,
                'wager_amount' => $betAmount,
                'payout_amount' => $winAmount,
                'bet_time' => $betTime,
                'status' => $status,
                'ref_id' => $refID
            ]);
    }

    public function createSettleTransaction(object $player, Request $request, string $settleTime): void
    {
        DB::connection('pgsql_write')
            ->table('pca.reports')
            ->insert([
                'play_id' => $player->play_id,
                'currency' => $player->currency,
                'game_code' => $request->gameCodeName,
                'bet_choice' => '-',
                'bet_id' => $request->gameRoundCode,
                'wager_amount' => 0,
                'payout_amount' => (float) $request->pay['amount'],
                'bet_time' => $settleTime,
                'status' => 'PAYOUT',
                'ref_id' => $request->pay['transactionCode']
            ]);
    }

    public function createLoseTransaction(object $player, Request $request): void
    {
        DB::connection('pgsql_write')
            ->table('pca.reports')
            ->insert([
                'play_id' => $player->play_id,
                'currency' => $player->currency,
                'game_code' => $request->gameCodeName,
                'bet_choice' => '-',
                'bet_id' => $request->gameRoundCode,
                'wager_amount' => 0,
                'payout_amount' => 0,
                'bet_time' => Carbon::now()->setTimezone('GMT+8')->format('Y-m-d H:i:s'),
                'status' => 'PAYOUT',
                'ref_id' => "L-{$request->requestId}"
            ]);
    }

    public function createRefundTransaction(object $player, Request $request, string $refundTime): void
    {
        DB::connection('pgsql_write')
            ->table('pca.reports')
            ->insert([
                'play_id' => $player->play_id,
                'currency' => $player->currency,
                'game_code' => $request->gameCodeName,
                'bet_choice' => '-',
                'bet_id' => $request->gameRoundCode,
                'wager_amount' => $request->pay['amount'],
                'payout_amount' => $request->pay['amount'],
                'bet_time' => $refundTime,
                'status' => 'REFUND',
                'ref_id' => "R-{$request->pay['relatedTransactionCode']}"
            ]);
    }
}