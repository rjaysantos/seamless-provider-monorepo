<?php

namespace Providers\Sbo\SportsbookDetails;

use Providers\Sbo\Contracts\ISboSportsbookDetails;

class SboCancelSportsbookDetails implements ISboSportsbookDetails
{
    public function __construct(
        protected string $trxID,
        protected string $ipAddress,
        protected object $transaction
    ) {
    }

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
        return 'void';
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

    public function getTicketID(): string
    {
        return $this->trxID;
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
