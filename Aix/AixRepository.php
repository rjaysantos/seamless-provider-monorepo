<?php

namespace Providers\Aix;

use App\Repositories\AbstractProviderRepository;
use Providers\Aix\DTO\AixPlayerDTO;
use Providers\Aix\DTO\AixTransactionDTO;

class AixRepository extends AbstractProviderRepository
{
    public function createIgnorePlayer(AixPlayerDTO $playerDTO): void
    {
        $this->write->table('aix.players')
            ->insertOrIgnore([
                'play_id' => $playerDTO->playID,
                'username' => $playerDTO->username,
                'currency' => $playerDTO->currency
            ]);
    }

    public function getPlayerByPlayID(string $playID): ?AixPlayerDTO
    {
        $data = $this->read->table('aix.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : AixPlayerDTO::fromDB(dbData: $data);
    }

    public function getTransactionByExtID(string $extID): ?AixTransactionDTO
    {
        $data = $this->read->table('aix.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : AixTransactionDTO::fromDB(dbData: $data);
    }

    public function createTransaction(AixTransactionDTO $transactionDTO): void
    {
        $this->write->table('aix.reports')
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
