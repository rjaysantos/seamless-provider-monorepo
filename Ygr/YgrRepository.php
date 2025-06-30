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

    public function createTransaction(YgrTransactionDTO $transactionDTO): void
    {
        $this->write->table('ygr.reports')
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

    public function resetPlayerToken(YgrPlayerDTO $playerDTO): void
    {
        $this->write->table('ygr.players')
            ->where('play_id', $playerDTO->playID)
            ->update(['token' => null]);
    }
}
