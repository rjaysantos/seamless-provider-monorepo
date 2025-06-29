<?php

namespace Providers\Ygr;

use Illuminate\Support\Facades\DB;
use Providers\Ygr\DTO\YgrPlayerDTO;
use Providers\Ygr\DTO\YgrTransactionDTO;
use App\Repositories\AbstractProviderRepository;

class YgrRepository extends AbstractProviderRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        $data = $this->read->table('ygr.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : YgrPlayerDTO::fromDB(dbData: $data);
    }

    public function getPlayerByToken(string $token): ?object
    {
        $data = $this->read->table('ygr.players')
            ->where('token', $token)
            ->first();

        return $data == null ? null : YgrPlayerDTO::fromDB(dbData: $data);
    }

    public function getTransactionByExtID(string $extID): ?YgrTransactionDTO
    {
        $data = $this->read->table('ygr.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : YgrTransactionDTO::fromDB(dbData: $data);
    }

    public function createOrIgnorePlayer(YgrPlayerDTO $playerDTO): void
    {
        $this->write->table('ygr.players')
            ->insertOrIgnore([
                'play_id' => $playerDTO->playID,
                'username' => $playerDTO->username,
                'currency' => $playerDTO->currency,
            ]);
    }

    public function updateOrInsertPlayerTokenAndGameID(YgrPlayerDTO $playerDTO, string $gameID): void
    {
        $this->write->table('ygr.players')
            ->updateOrInsert(
                ['play_id' => $playerDTO->playID],
                [
                    'token' => $playerDTO->token,
                    'game_code' => $gameID
                ]
            );
    }

    public function createTransaction(
        string $transactionID,
        float $betAmount,
        float $winAmount,
        string $transactionDate
    ): void {
        DB::connection('pgsql_write')
            ->table('ygr.reports')
            ->insert([
                'trx_id' => $transactionID,
                'bet_amount' => $betAmount,
                'win_amount' => $winAmount,
                'updated_at' => $transactionDate,
                'created_at' => $transactionDate
            ]);
    }

    public function resetPlayerToken(YgrPlayerDTO $playerDTO): void
    {
        $this->write->table('ygr.players')
            ->where('play_id', $playerDTO->playID)
            ->update(['token' => null]);
    }
}
