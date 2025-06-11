<?php

namespace Providers\Aix;

use Illuminate\Support\Facades\DB;
use Providers\Aix\DTO\AixPlayerDTO;
use Providers\Aix\DTO\AixTransactionDTO;

class AixRepository
{
    public function createIgnorePlayer(AixPlayerDTO $playerDTO): void
    {
        DB::connection('pgsql_report_write')
            ->table('aix.players')
            ->insertOrIgnore([
                'play_id' => $playerDTO->playID,
                'username' => $playerDTO->username,
                'currency' => $playerDTO->currency
            ]);
    }

    public function getPlayerByPlayID(string $playID): ?AixPlayerDTO
    {
        $data = DB::connection('pgsql_report_read')
            ->table('aix.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : AixPlayerDTO::fromDB(dbData: $data);
    }

    public function getTransactionByExtID(string $extID): ?AixTransactionDTO
    {
        $data = DB::connection('pgsql_report_read')
            ->table('aix.reports')
            ->where('ext_id', $extID)
            ->first();

        return $data == null ? null : AixTransactionDTO::fromDB(dbData: $data);
    }

    public function createTransaction(AixTransactionDTO $transactionDTO): void
    {
        DB::connection('pgsql_report_write')
            ->table('aix.reports')
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

    public function createSettleTransaction(
        AixTransactionDTO $betTransactionDTO,
        AixTransactionDTO $settleTransactionDTO
    ): void {
        DB::connection('pgsql_report_write')
            ->table('aix.reports')
            ->insert([
                'ext_id' => $betTransactionDTO->extID,
                'username' => $betTransactionDTO->username,
                'play_id' => $betTransactionDTO->playID,
                'web_id' => $betTransactionDTO->webID,
                'currency' => $betTransactionDTO->currency,
                'game_code' => $betTransactionDTO->gameID,
                'bet_amount' => $betTransactionDTO->betAmount,
                'bet_valid' => $betTransactionDTO->betValid,
                'bet_winlose' => $settleTransactionDTO->winAmount - $betTransactionDTO->betAmount,
                'updated_at' => $settleTransactionDTO->dateTime,
                'created_at' => $settleTransactionDTO->dateTime
            ]);
    }

    public function settleTransaction(AixTransactionDTO $transactionDTO, float $winAmount, string $updatedDateTime): void
    {
        DB::connection('pgsql_report_write')
            ->table('aix.reports')
            ->where('ext_id', $transactionDTO->extID)
            ->update([
                'bet_winlose' => $winAmount - $transactionDTO->betAmount,
                'updated_at' => $updatedDateTime,
            ]);
    }
}
