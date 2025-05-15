<?php

namespace App\GameProviders\V2\PLA;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\GameProviders\V2\PCA\Contracts\IRepository;

class PlaRepository implements IRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('pla.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function getPlayGameByPlayIDToken(string $playID, string $token): ?object
    {
        return DB::table('pla.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->first();
    }

    public function getBetTransactionByTransactionID(string $transactionID): ?object
    {
        return DB::table('pla.reports')
            ->where('trx_id', $transactionID)
            ->where('updated_at', null)
            ->first();
    }

    public function getTransactionByTransactionIDRefID(string $transactionID, string $refID): ?object
    {
        return DB::table('pla.reports')
            ->where('trx_id', $transactionID)
            ->where('ref_id', $refID)
            ->first();
    }

    public function getTransactionByRefID(string $refID): ?object
    {
        return DB::table('pla.reports')
            ->where('ref_id', $refID)
            ->first();
    }

    public function createPlayer(string $playID, string $currency, string $username): void
    {
        DB::connection('pgsql_write')
            ->table('pla.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency
            ]);
    }

    public function createOrUpdateToken(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('pla.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE'
                ]
            );
    }

    public function deleteToken(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('pla.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->delete();
    }

    public function createBetTransaction(object $player, Request $request, string $betTime): void
    {
        DB::connection('pgsql_write')
            ->table('pla.reports')
            ->insert([
                'trx_id' => $request->gameRoundCode,
                'bet_amount' => (float) $request->amount,
                'win_amount' => 0,
                'created_at' => $betTime,
                'updated_at' => null,
                'ref_id' => $request->transactionCode
            ]);
    }

    public function createSettleTransaction(object $player, Request $request, string $settleTime): void
    {
        DB::connection('pgsql_write')
            ->table('pla.reports')
            ->insert([
                'trx_id' => $request->gameRoundCode,
                'bet_amount' => 0,
                'win_amount' => (float) $request->pay['amount'],
                'created_at' => $settleTime,
                'updated_at' => $settleTime,
                'ref_id' => $request->pay['transactionCode']
            ]);
    }

    public function createLoseTransaction(object $player, Request $request): void
    {
        $settleTime = Carbon::now()->setTimezone('GMT+8')->format('Y-m-d H:i:s');

        DB::connection('pgsql_write')
            ->table('pla.reports')
            ->insert([
                'trx_id' => $request->gameRoundCode,
                'bet_amount' => 0,
                'win_amount' => 0,
                'created_at' => $settleTime,
                'updated_at' => $settleTime,
                'ref_id' => "L-{$request->requestId}"
            ]);
    }

    public function createRefundTransaction(object $player, Request $request, string $refundTime): void
    {
        DB::connection('pgsql_write')
            ->table('pla.reports')
            ->insert([
                'trx_id' => $request->gameRoundCode,
                'bet_amount' => $request->pay['amount'],
                'win_amount' => $request->pay['amount'],
                'created_at' => $refundTime,
                'updated_at' => $refundTime,
                'ref_id' => "R-{$request->pay['relatedTransactionCode']}"
            ]);
    }

    public function getBetTransactionByTransactionIDRefID(string $transactionID, string $refID): ?object
    {
        return DB::table('pla.reports')
            ->where('trx_id', $transactionID)
            ->where('updated_at', null)
            ->where('ref_id', $refID)
            ->first();
    }

    public function getRefundTransactionByTransactionIDRefID(string $transactionID, string $refID): ?object
    {
        return DB::table('pla.reports')
            ->where('trx_id', $transactionID)
            ->where('ref_id', "R-{$refID}")
            ->first();
    }

    public function updatePlayerStatusToJackpotBanned(string $playID): void
    {
        DB::connection('pgsql_write')
            ->table('pla.players')
            ->where('play_id', $playID)
            ->update(['limit' => 'jackpot banned']);
    }
}
