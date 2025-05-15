<?php

namespace App\GameProviders\V2\PLA;

use Wallet\V1\ProvSys\Transfer\Report;
use App\GameProviders\V2\PLA\Contracts\ICredentials;
use App\GameProviders\V2\PCA\Contracts\IWalletReport;

class PlaWalletReport implements IWalletReport
{
    const SLOTS = 'S';
    const ARCADE = 'A';

    public function makeReport(
        ICredentials $credentials,
        string $transactionID,
        string $gameCode,
        string $betTime
    ): Report {
        $gameCategory = in_array($gameCode, $credentials->getArcadeGameList()) === true ?
            self::ARCADE : self::SLOTS;

        $report = new Report;

        $report->setBetId($transactionID);
        $report->setGameCode($gameCode);
        $report->setGameCategory($gameCategory);
        $report->setBetTime($betTime);

        return $report;
    }
}