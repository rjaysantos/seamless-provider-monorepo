<?php

namespace Providers\Sbo\SportsbookDetails;

use Illuminate\Support\Str;
use Providers\Sbo\Contracts\ISboSportsbookDetails;

class SboSettleSportsbookDetails implements ISboSportsbookDetails
{
    public function __construct(
        protected object $betDetails,
        protected object $request,
        protected float $betAmount,
        protected string $ipAddress
    ) {}

    public function getGameCode(): string
    {
        return $this->getMarket();
    }

    public function getBetChoice(): string
    {
        $match = $this->betDetails->subBet[0]->match;
        $betOption = $this->betDetails->subBet[0]->betOption;

        $delimiter = Str::contains($match, '-vs-') ? '-vs-' : 'vs';

        [$home, $away] = explode($delimiter, $match);

        return match ($betOption) {
            1 => $home,
            2 => $away,
            'draw', 'X' => 'draw',
            default => $betOption,
        };
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
        return $this->betDetails->subBet[0]->sportType ?? 'Virtual Sports';
    }

    public function getEvent(): string
    {
        return $this->betDetails->subBet[0]->league ?? '-';
    }

    public function getMatch(): string
    {
        return $this->betDetails->subBet[0]->match;
    }

    public function getMarket(): string
    {
        return $this->betDetails->subBet[0]->marketType;
    }

    public function getHdp(): string
    {
        return $this->betDetails->subBet[0]->hdp;
    }

    public function getOdds(): float
    {
        return $this->betDetails->subBet[0]->odds;
    }

    public function getTicketID(): string
    {
        return $this->request->TransferCode;
    }

    public function getOddsType(): string
    {
        return match ($this->betDetails->oddsStyle) {
            'Malay' => 'M',
            'HongKong' => 'H',
            'Euro' => 'E',
            'Indo' => 'I',
            null => '-',
            default => $this->betDetails->oddsStyle
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
        ]);
    }
}
