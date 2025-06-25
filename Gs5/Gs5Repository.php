<?php

namespace Providers\Gs5;

use Illuminate\Support\Facades\DB;
use Providers\Gs5\DTO\Gs5PlayerDTO;
use Providers\Gs5\DTO\Gs5TransactionDTO;
use App\Repositories\AbstractProviderRepository;

class Gs5Repository extends AbstractProviderRepository
{
    public function getPlayerByToken(string $token): ?object
    {
        return DB::table('gs5.playgame')
            ->join('gs5.players', 'gs5.playgame.play_id', '=', 'gs5.players.play_id')
            ->where('gs5.playgame.token', $token)
            ->first();
    }

    public function getPlayerByPlayID(string $playID): ?object
    {
        return DB::table('gs5.players')
            ->where('play_id', $playID)
            ->first();
    }

    public function getTransactionByExtID(string $extID): ?Gs5TransactionDTO
    {
        $data = DB::table('gs5.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : Gs5TransactionDTO::fromDB(dbData: $data);
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        DB::connection('pgsql_write')
            ->table('gs5.players')
            ->insert([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
            ]);
    }

    public function createOrUpdatePlayer(Gs5PlayerDTO $playerDTO): void
    {
        $this->write->table('gs5.players')
            ->updateOrInsert(
                [
                    'play_id' => $playerDTO->playID,
                    'username' => $playerDTO->username,
                    'currency' => $playerDTO->currency,
                ],
                [
                    'game_code' => $playerDTO->gameCode,
                    'token' => $playerDTO->token,
                ]
            );
    }

    public function createOrUpdatePlayGame(string $playID, string $token): void
    {
        DB::connection('pgsql_write')
            ->table('gs5.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE'
                ]
            );
    }

    public function settleTransaction(string $trxID, float $winAmount, string $settleTime): void
    {
        DB::connection('pgsql_write')
            ->table('gs5.reports')
            ->where('trx_id', $trxID)
            ->update([
                'win_amount' => $winAmount,
                'updated_at' => $settleTime
            ]);
    }

    public function createWagerTransaction(
        string $trxID,
        float $betAmount,
        string $transactionDate,
    ): void {
        DB::connection('pgsql_write')
            ->table('gs5.reports')
            ->insert([
                'trx_id' => $trxID,
                'bet_amount' => $betAmount,
                'win_amount' => 0.00,
                'updated_at' => null,
                'created_at' => $transactionDate
            ]);
    }
}
