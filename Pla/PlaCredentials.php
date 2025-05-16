<?php

namespace Providers\Pla;

use Providers\Pla\Credentials\PlaIDR;
use Providers\Pla\Credentials\PlaMYR;
use Providers\Pla\Credentials\PlaPHP;
use Providers\Pla\Credentials\PlaTHB;
use Providers\Pla\Credentials\PlaUSD;
use Providers\Pla\Credentials\PlaVND;
use Providers\Pla\Contracts\ICredentials;
use Providers\Pla\Credentials\PlaStaging;

class PlaCredentials
{
    public function getCredentialsByCurrency(?string $currency): ICredentials
    {
        if (config('app.env') === 'PRODUCTION') {
            return match ($currency) {
                'IDR' => new PlaIDR,
                'PHP' => new PlaPHP,
                'THB' => new PlaTHB,
                'VND' => new PlaVND,
                'USD' => new PlaUSD,
                'MYR' => new PlaMYR,
            };
        }

        return new PlaStaging;
    }
}