<?php

namespace App\Contracts\V2;

interface ISportsbookDetails
{
    public function getGameCode(): string;
    public function getBetChoice(): string;
    public function getResult(): string;
    public function getSportsType(): string;
    public function getEvent(): string;
    public function getMatch(): string;
    public function getMarket(): string;
    public function getHdp(): string;
    public function getOdds(): float;
    public function getOpt(): string;
}
