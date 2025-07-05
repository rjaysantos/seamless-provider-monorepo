<?php

namespace Providers\Pla;

use Illuminate\Support\Facades\DB;
use Providers\Pla\DTO\PlaPlayerDTO;
use Providers\Pla\DTO\PlaTransactionDTO;
use App\Repositories\AbstractProviderRepository;

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

    public function getTransactionByExtID(string $extID): ?PlaTransactionDTO
    {
        $data = $this->read->table('pla.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : PlaTransactionDTO::fromDB(dbData: $data);
    }

    public function getTransactionByRefID(string $refID): ?object
    {
        return DB::table('pla.reports')
            ->where('ref_id', $refID)
            ->first();
    }

    public function createOrUpdatePlayer(PlaPlayerDTO $playerDTO): void
    {
        $this->write->table('pla.players')
            ->updateOrInsert(
                [
                    'play_id' => $playerDTO->playID,
                    'username' => $playerDTO->username,
                    'currency' => $playerDTO->currency,
                ],
                [
                    'token' => $playerDTO->token
                ]
            );
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

    public function resetPlayerToken(string $playID): void
    {
        $this->write->table('pla.players')
            ->where('play_id', $playID)
            ->update(['token' => null]);
    }

    public function createTransaction(PlaTransactionDTO $transactionDTO): void
    {
        $this->write->table('pla.reports')
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

    public function getBetTransactionByRoundID(string $roundID): ?PlaTransactionDTO
    {
        $data = $this->read->table('pla.reports')
            ->where('round_id', $roundID)
            ->first();

        return $data == null ? null : PlaTransactionDTO::fromDB(dbData: $data);
    }

    public function getBetTransactionByExtID(string $extID): ?PlaTransactionDTO
    {
        $data = $this->read->table('pla.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : PlaTransactionDTO::fromDB(dbData: $data);
    }
}
