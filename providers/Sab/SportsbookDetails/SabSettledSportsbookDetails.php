<?php

namespace Providers\Sab\SportsbookDetails;

use App\Contracts\V2\ISportsbookDetails;

class SabSettledSportsbookDetails implements ISportsbookDetails
{
    public function __construct(private object $settledTransactionDetails) {}

    public function getGameCode(): string
    {
        return $this->settledTransactionDetails->game_code;
    }

    public function getBetChoice(): string
    {
        return $this->settledTransactionDetails->bet_choice;
    }

    public function getResult(): string
    {
        return $this->settledTransactionDetails->result;
    }

    public function getSportsType(): string
    {
        return $this->settledTransactionDetails->sports_type;
    }

    public function getEvent(): string
    {
        return $this->settledTransactionDetails->event;
    }

    public function getMatch(): string
    {
        return $this->settledTransactionDetails->match;
    }

    public function getMarket(): string
    {
        return $this->settledTransactionDetails->event;
    }

    public function getHdp(): string
    {
        return $this->settledTransactionDetails->hdp;
    }

    public function getOdds(): float
    {
        return $this->settledTransactionDetails->odds;
    }

    public function getOpt(): string
    {
        return '-';
    }
}
