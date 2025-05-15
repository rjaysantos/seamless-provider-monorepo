<?php

namespace Providers\Sab\SportsbookDetails;

use Carbon\Carbon;
use Providers\Sab\Contracts\ISabSportsbookDetails;

class SabSportsbookDetails implements ISabSportsbookDetails
{
    public function __construct(protected object $sabSportsbookDetails, protected string $ipAddress) {}

    public function getGameCode(): string
    {
        return $this->sabSportsbookDetails->bet_type;
    }

    public function getBetChoice(): string
    {
        if (empty($this->sabSportsbookDetails->hometeamname[0]->name) || empty($this->sabSportsbookDetails->awayteamname[0]->name))
            return '-';

        $home = $this->sabSportsbookDetails->hometeamname[0]->name;
        $away = $this->sabSportsbookDetails->awayteamname[0]->name;

        $betChoice = $this->sabSportsbookDetails->bet_team;

        if ($betChoice == 1 || $betChoice == 'h')
            $betChoice = $home;
        else if ($betChoice == 2 || $betChoice == 'a')
            $betChoice = $away;
        else if ($betChoice == 'x' || $betChoice == 'draw')
            $betChoice = 'draw';

        return $betChoice;
    }

    public function getResult(): string
    {
        return $this->sabSportsbookDetails->ticket_status;
    }

    public function getSportsType(): string
    {
        return $this->sabSportsbookDetails->sportname[0]->name;
    }

    public function getEvent(): string
    {
        return $this->sabSportsbookDetails->leaguename[0]->name ?? '-';
    }

    public function getMatch(): string
    {
        if (empty($this->sabSportsbookDetails->hometeamname[0]->name) || empty($this->sabSportsbookDetails->awayteamname[0]->name))
            return '-';

        return $this->sabSportsbookDetails->hometeamname[0]->name . ' vs ' . $this->sabSportsbookDetails->awayteamname[0]->name;
    }

    public function getMarket(): string
    {
        return $this->sabSportsbookDetails->bettypename[0]->name;
    }

    public function getHdp(): string
    {
        return $this->sabSportsbookDetails->hdp ?? '-';
    }

    public function getOdds(): float
    {
        return $this->sabSportsbookDetails->odds ?? 0;
    }

    public function getTicketID(): string
    {
        return $this->sabSportsbookDetails->trans_id;
    }

    public function getOddsType(): string
    {
        return match ($this->sabSportsbookDetails->odds_type) {
            1 => 'Malay Odds',
            2 => 'China Odds',
            3 => 'Decimal Odds',
            4 => 'Indo Odds',
            5 => 'American Odds',
            6 => 'Myanmar Odds',
            default => $this->sabSportsbookDetails->odds_type
        };
    }

    public function getStake(): float
    {
        return $this->sabSportsbookDetails->stake;
    }

    public function getScore(): string
    {
        if (empty($this->sabSportsbookDetails->home_score) || empty($this->sabSportsbookDetails->away_score))
            return '-';

        return $this->sabSportsbookDetails->home_score . ' : ' . $this->sabSportsbookDetails->away_score;
    }

    public function getOpt(): string
    {
        return json_encode([
            'betId' => $this->getTicketID(),
            'is_first_half' => 0,
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

    public function getSingleParlayBets(): array
    {
        $singleParlayBets = [];
        if (isset($this->sabSportsbookDetails->SingleParlayData) == true)
            foreach ($this->sabSportsbookDetails->SingleParlayData as $betDetails) {
                $singleParlayBets[] = (object)[
                    'betChoice' => $betDetails->selection_name,
                    'status' => $betDetails->status
                ];
            }

        return $singleParlayBets;
    }

    public function getMixParlayBets(): array
    {
        return [];
    }

    public function getDateTimeSettle(): string
    {
        if (is_null($this->sabSportsbookDetails->settlement_time) == true)
            return '-';

        return Carbon::parse($this->sabSportsbookDetails->settlement_time)->format('d M Y H:i:s');
    }
}
