<?php

namespace Providers\Ors;

use App\Libraries\Randomizer;
use Illuminate\Support\Facades\DB;
use Providers\Ors\DTO\OrsPlayerDTO;
use Providers\Ors\DTO\OrsTransactionDTO;
use App\Repositories\AbstractProviderRepository;

class OrsRepository extends AbstractProviderRepository
{
    public function __construct(private Randomizer $randomizer)
    {
        parent::__construct();
    }

    public function getPlayerByPlayID(string $playID): ?object
    {
        $data = $this->read->table('ors.players')
            ->where('play_id', $playID)
            ->first();

        return $data == null ? null : OrsPlayerDTO::fromDB(dbData: $data);
    }

    public function createPlayer(string $playID, string $username, string $currency): void
    {
        DB::connection('pgsql_report_write')
            ->table('ors.players')
            ->insertOrIgnore([
                'play_id' => $playID,
                'username' => $username,
                'currency' => $currency,
            ]);
    }

    public function createToken(string $playID): string
    {
        $token = $this->randomizer->createToken();

        DB::connection('pgsql_report_write')
            ->table('ors.playgame')
            ->updateOrInsert(
                ['play_id' => $playID],
                [
                    'token' => $token,
                    'expired' => 'FALSE'
                ]
            );

        return $token;
    }

    public function getTransactionByExtID(string $extID): ?object
    {
        return DB::connection('pgsql_report_read')
            ->table('ors.reports')
            ->where('ext_id', $extID)
            ->first();
    }

    public function getPlayGameByPlayIDToken(string $playID, string $token): ?object
    {
        return DB::connection('pgsql_report_read')
            ->table('ors.playgame')
            ->where('play_id', $playID)
            ->where('token', $token)
            ->first();
    }

    public function createTransaction(OrsTransactionDTO $transactionDTO): void
    {
        $this->write->table('ors.reports')
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
