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

    public function getPlayerByUserIDProvider(int $userIDProvider): ?object
    {
        return $this->read->table('red.players')
            ->where('user_id_provider', $userIDProvider)
            ->first();
    }

    public function getTransactionByExtID(string $extID): ?RedTransactionDTO
    {
        $data = $this->read->table('red.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : RedTransactionDTO::fromDB(dbData: $data);
    }

    public function createPlayer(string $playID, string $currency, int $userIDProvider): void
    {
        $this->write->table('red.players')
            ->insert([
                'play_id' => $playID,
                'username' => $playID,
                'currency' => $currency,
                'user_id_provider' => $userIDProvider
            ]);
    }

    private function getWebID(string $playID)
    {
        if (preg_match_all('/u(\d+)/', $playID, $matches)) {
            $lastNumber = end($matches[1]);
            return $lastNumber;
        }
    }

    public function createTransaction(
        string $extID,
        string $playID,
        string $username,
        string $currency,
        string $gameCode,
        float $betAmount,
        float $betWinlose,
        string $transactionDate,
    ): void {
        $this->write->table('red.reports')
            ->insert([
                'ext_id' => $extID,
                'username' => $username,
                'play_id' => $playID,
                'web_id' => $this->getWebID($playID),
                'currency' => $currency,
                'game_code' => $gameCode,
                'bet_amount' => $betAmount,
                'bet_valid' => $betAmount,
                'bet_winlose' => $betWinlose,
                'updated_at' => $transactionDate,
                'created_at' => $transactionDate
            ]);
    }
}
