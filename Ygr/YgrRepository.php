<?php

namespace Providers\Ygr;

use Illuminate\Support\Facades\DB;
use Providers\Ygr\DTO\YgrPlayerDTO;
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
        return DB::table('ygr.playgame')
            ->join('ygr.players', 'ygr.playgame.play_id', '=', 'ygr.players.play_id')
            ->where('ygr.playgame.token', $token)
            ->first();
    }

    public function getTransactionByTrxID(string $transactionID): ?object
    {
        return DB::table('ygr.reports')
            ->where('trx_id', $transactionID)
            ->first();
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

    public function updatePlayerTokenAndGameID(YgrPlayerDTO $playerDTO, string $token, string $gameID): void
    {
        $this->write->table('ygr.players')
            ->updateOrInsert(
                ['play_id' => $playerDTO->playID],
                [
                    'token' => $token,
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

    public function deletePlayGameByToken(string $token): void
    {
        DB::connection('pgsql_write')
            ->table('ygr.playgame')
            ->where('token', $token)
            ->delete();
    }
}
