<?php

namespace Providers\Pla;

use App\DTO\PlayerDTO;
use App\Repositories\AbstractProviderRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Providers\Pla\DTO\PlaPlayerDTO;

class PlaRepository extends AbstractProviderRepository
{
    public function getPlayerByPlayID(string $playID): ?PlaPlayerDTO
    {
        $data = $this->read->table('pla.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : PlaPlayerDTO::fromDB(dbData: $data);
    }

    public function getPlayerByPlayIDToken(string $playID, string $token): ?PlaPlayerDTO
    {
        $data = $this->read->table('pla.players')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->first();

        return $data == null ? null : PlaPlayerDTO::fromDB(dbData: $data);
    }

    public function getTransactionByTrxID(string $trxID): ?object
    {
        return DB::table('pla.reports')
            ->where('trx_id', $trxID)
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
        $this->write->table('pla.players')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->update(['token' => null]);
    }

    public function createTransaction(
        string $trxID,
        float $betAmount,
        float $winAmount,
        string $betTime,
        ?string $settleTime,
        string $refID
    ): void {
        DB::connection('pgsql_write')
            ->table('pla.reports')
            ->insert([
                'trx_id' => $trxID,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'created_at' => $betTime,
                'updated_at' => $settleTime,
                'ref_id' => $refID
            ]);
    }

    public function getBetTransactionByRefID(string $refID): ?object
    {
        return DB::table('pla.reports')
            ->where('ref_id', $refID)
            ->where('updated_at', null)
            ->first();
    }

    public function getBetTransactionByTrxID(string $trxID): ?object
    {
        return DB::table('pla.reports')
            ->where('trx_id', $trxID)
            ->where('updated_at', null)
            ->first();
    }
}
