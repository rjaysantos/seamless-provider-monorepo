<?php

namespace Providers\Gs5;

use Illuminate\Support\Facades\DB;
use Providers\Gs5\DTO\Gs5PlayerDTO;
use Providers\Gs5\DTO\Gs5TransactionDTO;
use App\Repositories\AbstractProviderRepository;

class Gs5Repository extends AbstractProviderRepository
{
    public function getPlayerByPlayID(string $playID): ?Gs5PlayerDTO
    {
        $data = DB::table('gs5.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : Gs5PlayerDTO::fromDB(dbData: $data);
    }

    public function getPlayerByToken(string $token): ?Gs5PlayerDTO
    {
        $data = $this->read->table('gs5.players')
            ->where('token', $token)
            ->first();

        return $data == null ? null : Gs5PlayerDTO::fromDB(dbData: $data);
    }

    public function getTransactionByExtID(string $extID): ?Gs5TransactionDTO
    {
        $data = $this->read->table('gs5.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : Gs5TransactionDTO::fromDB(dbData: $data);
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

    public function createTransaction(Gs5TransactionDTO $transactionDTO): void
    {
        $this->write->table('gs5.reports')
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
