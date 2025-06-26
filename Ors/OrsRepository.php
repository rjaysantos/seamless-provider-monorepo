<?php

namespace Providers\Ors;

use Illuminate\Support\Facades\DB;
use Providers\Ors\DTO\OrsPlayerDTO;
use App\Repositories\AbstractProviderRepository;
use Providers\Ors\DTO\OrsTransactionDTO;

class OrsRepository extends AbstractProviderRepository
{
    public function getPlayerByPlayID(string $playID): ?OrsPlayerDTO
    {
        $data = $this->read->table('ors.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : OrsPlayerDTO::fromDB(dbData: $data);
    }

    public function createOrUpdatePlayer(OrsPlayerDTO $playerDTO): void
    {
        $this->write->table('ors.players')
            ->updateOrInsert(
                ['play_id' => $playerDTO->playID],
                [
                    'username'   => $playerDTO->username,
                    'currency'   => $playerDTO->currency,
                    'token'      => $playerDTO->token
                ]
            );
    }

    public function getTransactionByExtID(string $extID): ?OrsTransactionDTO
    {
        $data = $this->read->table('ors.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : OrsTransactionDTO::fromDB(dbData: $data);
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

    public function createTransaction(OrsTransactionDTO $transactionDTO)
    {
        $this->write->table('ors.reports')
            ->insert([
                'ext_id' => $transactionDTO->extID,
                'round_id' => $transactionDTO->roundID,
                'username' => $transactionDTO->username,
                'play_id' => $transactionDTO->playID,
                'web_id' => $transactionDTO->webID,
                'currency' => $transactionDTO->currency,
                'game_code' => $transactionDTO->gameID,
                'bet_amount' => $transactionDTO->betAmount,
                'bet_valid' => $transactionDTO->betValid,
                'bet_winlose' => $transactionDTO->betWinlose,
                'updated_at' => $transactionDTO->dateTime,
                'created_at' => $transactionDTO->dateTime
            ]);
    }
}
