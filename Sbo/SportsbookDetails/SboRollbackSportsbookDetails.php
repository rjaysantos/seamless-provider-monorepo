<?php

namespace Providers\Sbo\SportsbookDetails;

use Providers\Sbo\Contracts\ISboSportsbookDetails;

class SboRollbackSportsbookDetails implements ISboSportsbookDetails
{
    public function __construct(protected object $transaction) {}

    public function getGameCode(): string
    {
        return $this->transaction->game_code;
    }

    public function getBetChoice(): string
    {
        return $this->transaction->bet_choice;
    }

    public function getResult(): string
    {
        return '-';
    }

    public function getSportsType(): string
    {
        return $this->transaction->sports_type;
    }

    public function getEvent(): string
    {
        return $this->transaction->event;
    }

    public function getMatch(): string
    {
        return $this->transaction->match;
    }

    public function getMarket(): string
    {
        return '-';
    }

    public function getHdp(): string
    {
        return $this->transaction->hdp;
    }

    public function getOdds(): float
    {
        return $this->transaction->odds;
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