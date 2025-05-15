<?php

namespace App\Libraries\Wallet\V2;

use App\Contracts\V2\ISportsbookDetails;
use Wallet\V1\ProvSys\Transfer\Report;

class WalletReport
{
    const SLOTS = 'S';
    const SLOTS_BONUS = 'B';
    const ARCADE = 'A';
    const SPORTSBOOK = 'SB';
    const SPORTSBOOK_BONUS = 'BONUS';
    const CASINO = 'C';

    public function makeSlotReport(
        string $transactionID,
        string $gameCode,
        string $betTime,
        string $opt = null
    ): Report {
        $report = new Report;

        $report->setBetId($transactionID);
        $report->setGameCode($gameCode);
        $report->setGameCategory(self::SLOTS);
        $report->setBetTime($betTime);

        if (is_null($opt) === false)
            $report->setOpt($opt);

        return $report;
    }

    public function makeArcadeReport(
        string $transactionID,
        string $gameCode,
        string $betTime,
        string $opt = null
    ): Report {
        $report = new Report;

        $report->setBetId($transactionID);
        $report->setGameCode($gameCode);
        $report->setGameCategory(self::ARCADE);
        $report->setBetTime($betTime);

        if (is_null($opt) === false)
            $report->setOpt($opt);

        return $report;
    }

    public function makeBonusReport(string $transactionID, string $gameCode, string $betTime): Report
    {
        $report = new Report;

        $report->setBetId($transactionID);
        $report->setGameCode($gameCode);
        $report->setGameCategory(self::SLOTS_BONUS);
        $report->setBetTime($betTime);

        return $report;
    }

    public function makeSportsbookReport(
        string $trxID,
        string $betTime,
        ISportsbookDetails $sportsbookDetails
    ): Report {
        $report = new Report;
        $report->setBetId($trxID);
        $report->setBetTime($betTime);
        $report->setGameCode($sportsbookDetails->getGameCode());
        $report->setGameCategory(self::SPORTSBOOK);
        $report->setBetChoice($sportsbookDetails->getBetChoice());
        $report->setResult($sportsbookDetails->getResult());
        $report->setSportsType($sportsbookDetails->getSportsType());
        $report->setEvent($sportsbookDetails->getEvent());
        $report->setMatch($sportsbookDetails->getMatch());
        $report->setMarket($sportsbookDetails->getMarket());
        $report->setHdp($sportsbookDetails->getHdp());
        $report->setOdds($sportsbookDetails->getOdds());
        $report->setOpt($sportsbookDetails->getOpt());

        return $report;
    }

    public function makeCasinoReport(
        string $trxID,
        string $gameCode,
        string $betTime,
        string $betChoice,
        string $result
    ): Report {
        $report = new Report;
        $report->setBetId($trxID);
        $report->setGameCode($gameCode);
        $report->setGameCategory(self::CASINO);
        $report->setBetTime($betTime);
        $report->setBetChoice($betChoice);
        $report->setResult($result);

        return $report;
    }
}
