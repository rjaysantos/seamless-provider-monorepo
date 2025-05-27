<?php

namespace Providers\Sbo\SportsbookDetails;

use Providers\Sbo\Contracts\ISboSportsbookDetails;

class SboRunningSportsbookDetails implements ISboSportsbookDetails
{
    public function __construct(protected int $gameCode)
    {
    }

    public function getGameCode(): string
    {
        return $this->gameCode;
    }

    public function getBetChoice(): string
    {
        return '-';
    }

    public function getResult(): string
    {
        return '-';
    }

    public function getSportsType(): string
    {   
        return '-';
    }

    public function getEvent(): string
    {
        return '-';
    }

    public function getMatch(): string
    {
        return '-';
    }

    public function getMarket(): string
    {
        return '-';
    }

    public function getHdp(): string
    {
        return '-';
    }

    public function getOdds(): float
    {
        return 0;
    }

    public function getOpt(): string
    {
        return '-';
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
        return 0;
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
}