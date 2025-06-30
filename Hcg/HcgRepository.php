<?php

namespace Providers\Hcg;

use Illuminate\Support\Facades\DB;
use Providers\Hcg\DTO\HcgPlayerDTO;
use Providers\Hcg\DTO\HcgTransactionDTO;
use App\Repositories\AbstractProviderRepository;

class HcgRepository extends AbstractProviderRepository
{
    public function getPlayerByPlayID(string $playID): ?HcgPlayerDTO
    {
        $data = $this->read->table('hcg.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : HcgPlayerDTO::fromDB(dbData: $data);
    }

    public function createPlayer(HcgPlayerDTO $playerDTO): void
    {
        $this->write->table('hcg.players')
            ->insert([
                'play_id' => $playerDTO->playID,
                'username' => $playerDTO->username,
                'currency' => $playerDTO->currency,
            ]);
    }

    public function getTransactionByExtID(string $extID): ?HcgTransactionDTO
    {
        $data = $this->read->table('hcg.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : HcgTransactionDTO::fromDB(dbData: $data);
    }

    public function createTransaction(HcgTransactionDTO $transactionDTO): void
    {
        $this->write->table('hcg.reports')
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