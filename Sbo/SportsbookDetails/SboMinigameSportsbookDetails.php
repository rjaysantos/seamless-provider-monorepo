<?php

namespace Providers\Sbo\SportsbookDetails;

use Providers\Sbo\Contracts\ISboSportsbookDetails;

class SboMinigameSportsbookDetails implements ISboSportsbookDetails
{
    public function __construct(
        protected string $gameCode,
        protected float $winloss,
        protected float $betAmount,
        protected bool $isCashOut,
        protected string $ipAddress
    ) {}

    public function getGameCode(): string
    {
        if ($this->gameCode === 285)
            $gameID = 'Mini Mines';
        if ($this->gameCode === 286)
            $gameID = 'Mini Football Strike';

        return $gameID ?? '-';
    }

    public function getBetChoice(): string
    {
        return 'ARCADE';
    }

    public function getResult(): string
    {
        if ($this->winloss > $this->betAmount)
            $result = 'win';
        elseif ($this->winloss == $this->betAmount)
            $result = 'draw';
        else
            $result = 'lose';

        if ($this->isCashOut === true)
            $result = 'cash out';

        return $result;
    }

    public function getSportsType(): string
    {
        return 'ARCADE';
    }

    public function getEvent(): string
    {
        return 'ARCADE';
    }

    public function getMatch(): string
    {
        return 'ARCADE';
    }

    public function getHdp(): string
    {
        return '-';
    }

    public function getOdds(): float
    {
        return 0;
    }

    public function getMarket(): string
    {
        return 'ARCADE';
    }

    public function getTicketID(): string
    {
        return '-';
    }

    public function getOddsType(): string
    {
        return '-';
    }

    public function getStake(): float
    {
        return '-';
    }

    public function getScore(): string
    {
        return '-';
    }

    public function getMixParlayBets(): array
    {
        return [];
    }

    public function getDateTimeSettle(): string
    {
        return '-';
    }

    public function getSingleParlayBets(): array
    {
        return [];
    }

    public function getOpt(): string
    {
        return json_encode([
            'betId' => $this->getTicketID(),
            'is_first_half' => strpos($this->getMarket(), 'First Half') !== false ? 1 : 0,
            'league' => $this->getEvent(),
            'score' => '-',
            'running_score' => '-',
            'halfScore' => '-',
            'match' => $this->getMatch(),
            'odds' => $this->getOdds(),
            'market' => $this->getMarket(),
            'odds_type' => $this->getOddsType(),
            'resultType' => $this->getResult(),
            'ip_address' => $this->ipAddress
        ]);
    }
}
