<?php

namespace Providers\Sab\SportsbookDetails;

use Providers\Sab\Contracts\ISabSportsbookDetails;
use Providers\Sab\SportsbookDetails\SabSportsbookDetails;

class SabMixParlaySportsbookDetails extends SabSportsbookDetails implements ISabSportsbookDetails
{
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

    public function getHdp(): string
    {
        return '-';
    }

    public function getScore(): string
    {
        return '-';
    }

    public function getMixParlayBets(): array
    {
        $mixParlayBets = [];

        foreach ($this->sabSportsbookDetails->ParlayData as $betDetails) {
            $mixParlayDetails = new SabSportsbookDetails(sabSportsbookDetails: $betDetails, ipAddress: $this->ipAddress);

            $mixParlayBets[] = (object)[
                'event' => $mixParlayDetails->getEvent(),
                'match' => $mixParlayDetails->getMatch(),
                'betType' => $mixParlayDetails->getMarket(),
                'betChoice' => $mixParlayDetails->getBetChoice(),
                'hdp' => $mixParlayDetails->getHdp(),
                'odds' => $mixParlayDetails->getOdds(),
                'score' => $mixParlayDetails->getScore(),
                'status' => $mixParlayDetails->getResult(),
            ];
        }

        return $mixParlayBets;
    }

    public function getSingleParlayBets(): array
    {
        return [];
    }
}
