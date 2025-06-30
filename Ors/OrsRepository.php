<?php

namespace Providers\Ors;

use Illuminate\Support\Facades\DB;
use Providers\Ors\DTO\OrsPlayerDTO;
use Providers\Ors\DTO\OrsTransactionDTO;
use App\Repositories\AbstractProviderRepository;

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
                [
                    'play_id' => $playerDTO->playID,
                    'username' => $playerDTO->username,
                    'currency' => $playerDTO->currency,
                ],
                [
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

    public function getPlayerByPlayIDToken(string $playID, string $token): ?OrsPlayerDTO
    {
        $data = $this->read->table('ors.players')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->first();

        return $data == null ? null : OrsPlayerDTO::fromDB(dbData: $data);
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
