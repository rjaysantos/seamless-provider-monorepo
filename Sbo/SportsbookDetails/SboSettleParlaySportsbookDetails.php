<?php

namespace Providers\Sbo\SportsbookDetails;

use Providers\Sbo\Contracts\ISboSportsbookDetails;

class SboSettleParlaySportsbookDetails implements ISboSportsbookDetails
{
    public function __construct(
        protected object $request,
        protected float $betAmount,
        protected float $odds,
        protected string $oddsStyle,
        protected string $ipAddress
    ) {}

    public function getGameCode(): string
    {
        return 'Mix Parlay';
    }

    public function getBetChoice(): string
    {
        return '-';
    }

    public function getResult(): string
    {
        if ($this->request->WinLoss > $this->betAmount)
            $result = 'win';
        elseif ($this->request->WinLoss == $this->betAmount)
            $result = 'draw';
        else
            $result = 'lose';

        if ($this->request->IsCashOut === true)
            $result = 'cash out';

        return $result;
    }

    public function getSportsType(): string
    {
        return 'Mix Parlay';
    }

    public function getEvent(): string
    {
        return '-';
    }

    public function getMatch(): string
    {
        return 'Mix Parlay';
    }

    public function getMarket(): string
    {
        return '-';
    }

    public function getHdp(): string
    {
        return '0';
    }

    public function getOdds(): float
    {
        return $this->odds;
    }

    public function getTicketID(): string
    {
        return $this->request->TransferCode;
    }

    public function getOddsType(): string
    {
        return match ($this->oddsStyle) {
            'Malay' => 'M',
            'HongKong' => 'H',
            'Euro' => 'E',
            'Indo' => 'I',
            default => $this->oddsStyle
        };
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
        ]);;
    }
}
