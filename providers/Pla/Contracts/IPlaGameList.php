<?php

namespace App\GameProviders\V2\PLA\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface IPlaGameList extends IWalletCredentials
{
    public function getArcadeGameList(): array;
}
