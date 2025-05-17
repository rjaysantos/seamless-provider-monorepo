<?php

namespace Providers\Sbo\Contracts;

use App\Contracts\V2\ISportsbookDetails;

interface ISboSportsbookDetails extends ISportsbookDetails
{
    public function getTicketID(): string;
    public function getOddsType(): string;
    public function getStake(): float;
    public function getScore(): string;
    public function getMixParlayBets(): array;
    public function getDateTimeSettle(): string;
    public function getSingleParlayBets(): array;
}
