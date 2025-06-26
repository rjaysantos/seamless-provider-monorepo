<?php

namespace Providers\Hcg;

use Illuminate\Support\Facades\DB;
use Providers\Hcg\DTO\HcgPlayerDTO;
use Providers\Hcg\DTO\HcgTransactionDTO;
use App\Repositories\AbstractProviderRepository;

class HcgRepository extends AbstractProviderRepository
{
    public function getPlayerByPlayID(string $playID): ?HcgPlayerDTO
    {
        $data = $this->read->table('hcg.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : HcgPlayerDTO::fromDB(dbData: $data);
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        DB::connection('pgsql_write')
            ->table('hcg.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
            ]);
    }

    public function getTransactionByExtID(string $extID): ?object
    {
        $data = $this->read->table('hcg.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : HcgTransactionDTO::fromDB(dbData: $data);
    }

    public function createSettleTransaction(
        string $transactionID,
        float $betAmount,
        float $winAmount,
        string $settleTime
    ): void {
        DB::connection('pgsql_write')
            ->table('hcg.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'created_at' => $settleTime,
                'updated_at' => $settleTime,
            ]);
    }
}