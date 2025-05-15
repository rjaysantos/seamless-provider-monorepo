<?php

namespace Providers\Sab\SportsbookDetails;

use App\Contracts\V2\ISportsbookDetails;

class SabRunningSportsbookDetails implements ISportsbookDetails
{
    public function __construct(private string $gameCode) {}

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
}
