<?php

namespace Providers\Pla\Contracts;

use App\Contracts\V2\IWalletCredentials;

interface IPlaGameList extends IWalletCredentials
{
    public function getArcadeGameList(): array;
}
