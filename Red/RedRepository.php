<?php

namespace Providers\Red;

use Providers\Red\DTO\RedPlayerDTO;
use App\Repositories\AbstractProviderRepository;
use Providers\Red\DTO\RedTransactionDTO;

class RedRepository extends AbstractProviderRepository
{
    public function getPlayerByPlayID(string $playID): ?object
    {
        $data = $this->read->table('red.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : RedPlayerDTO::fromDB(dbData: $data);
    }

    public function createIgnorePlayer(RedPlayerDTO $playerDTO, int $providerUserID): void
    {
        $this->write->table('red.players')
            ->insertOrIgnore([
                'play_id' => $playerDTO->playID,
                'username' => $playerDTO->username,
                'currency' => $playerDTO->currency,
                'user_id_provider' => $providerUserID
            ]);
    }

    public function getPlayerByUserIDProvider(int $providerUserID): ?object
    {
        $data = $this->read->table('red.players')
            ->where('user_id_provider', $providerUserID)
            ->first();

        return $data == null ? null : RedPlayerDTO::fromDB(dbData: $data);
    }

    public function getTransactionByExtID(string $extID): ?RedTransactionDTO
    {
        $data = $this->read->table('red.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : RedTransactionDTO::fromDB(dbData: $data);
    }

    public function createTransaction(RedTransactionDTO $transactionDTO): void
    {
        $this->write->table('red.reports')
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
